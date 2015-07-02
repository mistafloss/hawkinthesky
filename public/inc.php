<?php

/**
 * @author  oke.ugwu
 */
class Hawk
{
  private static $_dbConn;
  public static $config;

  public static function init()
  {
    self::$config = include '../config.php';
  }

  /**
   * @return \Simplon\Mysql\Mysql
   */
  public static function dbConn()
  {
    if(self::$_dbConn == null)
    {
      self::init();
      self::$_dbConn = new \Simplon\Mysql\Mysql(
        self::$config['database']['host'],
        self::$config['database']['username'],
        self::$config['database']['password'],
        self::$config['database']['database']
      );
    }

    return self::$_dbConn;
  }


  public static function registerProperties(Google_Service_Analytics $service)
  {
    $accounts = $service->management_accounts
      ->listManagementAccounts()->getItems();
    /**
     * @var Google_Service_Analytics_Account $account
     */
    $webProperties = $service->management_webproperties;
    $return        = [];
    $data          = [];
    foreach($accounts as $account)
    {
      $data['ga_account_id'] = $account->getId();
      $properties            = $webProperties->listManagementWebproperties(
        $data['ga_account_id']
      );
      /**
       * @var Google_Service_Analytics_Webproperty $property
       */
      foreach($properties as $property)
      {
        $data['name']           = $property->getName();
        $data['ga_property_id'] = $property->getDefaultProfileId();
        $data['url']            = $property->getWebsiteUrl();
        $data['time']           = time();

        $return[] = $data;

        //TODO - proper handling of duplicate keys and other sql errors
        try
        {
          self::dbConn()->insert('websites', $data, true);
        }
        catch(Exception $e)
        {
          error_log($e->getMessage());
        }
      }
    }

    return $return;
  }

  public static function getProperties($includeDisabled = false)
  {
    $append = ($includeDisabled) ? '' : ' WHERE disabled = 0';
    return self::dbConn()->fetchRowMany("SELECT * FROM websites" . $append);
  }

  public static function metricsName($name)
  {
    $lookup = [
      'ga:sessions'        => 'Sessions',
      'ga:pageviews'       => 'Page Views',
      'ga:uniquePageviews' => 'Unique PageViews',
      'ga:users'           => 'Users',
      'ga:newUsers'        => 'New Users',
      'ga:avgPageLoadTime' => 'Avg. Page Load Time',
      'ga:bounceRate'      => 'Bounce Rate'
    ];

    return isset($lookup[$name]) ? $lookup[$name] : '--';
  }
}

Hawk::init();
