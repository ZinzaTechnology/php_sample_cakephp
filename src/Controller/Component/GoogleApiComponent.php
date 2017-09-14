<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Routing\Router;

/**
* Get data through Google API
*
* @package GoogleApiComponent
*
* @var Google_Client $client
* @var Google_Service_Webmasters $serviceWebmasters
* @var Google_Service_People $servicePeople
* @var bool $isAuthorized
* @var object $access_token
*/
class GoogleApiComponent extends Component
{
    // Defind SearchAnalytics's parameters' options
    const SEARCH_TYPE_OPTIONS = ['web', 'image', 'video'];
    const AGGREGATION_TYPE_OPTIONS = ['auto', 'byPage', 'byProperty'];
    const DIMENSION_OPTIONS = ['query', 'country', 'device', 'page'];
    //const DIMENSION_OPTIONS = ['query', 'country', 'device', 'page', 'searchAppearance'];
    const OPERATOR_OPTIONS = ['contains', 'equals', 'notContains', 'notEquals'];

    // Set access type offline to be able to get data while people is offline
    const ACCESS_TYPE = 'offline'; // 'online'

    /**
     * Init component with its properties
     * 
     * @param array $config default
     * 
     * @return null
     */
    public function initialize(array $config)
    {
        $this->client = $this->_initGoogleClient();

        // Init services
        $this->serviceWebmasters = new \Google_Service_Webmasters($this->client);
        $this->servicePeople = new \Google_Service_People($this->client);

        $this->isAuthorized = false;
        $this->session = $this->request->session();

        // Read access_token from session
        if ($this->session->check('access_token')) {
            try {
                $this->access_token = $this->session->read('access_token');
                $this->client->setAccessToken($this->access_token);
                $this->isAuthorized = true;
            } catch (\InvalidArgumentException $ex) {
                $this->isAuthorized = false;
            }
        }
    }

    /**
     * Init Google Client
     * 
     * @return Google_Client object
     */
    private function _initGoogleClient()
    {
        $client = new \Google_Client();

        // Read Web Application's client_secrets
        $client->setAuthConfig(dirname(__DIR__) . '/Component/client_secrets.json');

        $client->setAccessType(self::ACCESS_TYPE);
        $client->setIncludeGrantedScopes(true);

        // Define callback url
        // Need to register this url in the Credentials screen
        $callbackUrl = Router::url(
            [
            'controller' => 'GoogleApis',
            'action' => 'callback'
            ], true
        );
        $client->setRedirectUri($callbackUrl);

        // Define GoogleAPI's scopes
        $client->setScopes(
            [
            'https://www.googleapis.com/auth/webmasters.readonly',
            'https://www.googleapis.com/auth/webmasters',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            ]
        );
        return $client;
    }

    /**
     * Generate Authenticate url
     * 
     * @return string
     */
    public function createAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Authenticate using returned code from Google
     * 
     * @param string $code Get from callback url
     * 
     * @return null
     */
    public function authenticate($code)
    {
        $this->client->authenticate($code);
        $this->session->write('access_token', $this->client->getAccessToken());
    }

    /**
     * Revoke authorized token
     * 
     * @return null
     */
    public function revokeToken()
    {
        $this->client->revokeToken($this->access_token);
        $this->clearSearchParams();
        $this->session->delete('access_token');
        $this->session->delete('user_info');
    }

    /**
     * Get some user information by People API
     * 
     * @return Array|null
     */
    public function getUserInfo()
    {
        if (!$this->isAuthorized) {
            return;
        }
        // There's a limit number of time we can call People API a day
        // So we need to save user data into session to reduce the request number
        if ($this->session->check('user_info')) {
            $userInfo = $this->session->read('user_info');
        } else {
            $people =  $this->servicePeople->people->get('people/me');
            $userInfo['name'] = $people['names'] ? $people['names'][0]['displayName'] : '';
            $userInfo['email'] = $people['emailAddresses'] ? $people['emailAddresses'][0]['value'] : '';
            $this->session->write('user_info', $userInfo);
        }
        return $userInfo;
    }

    /**
     * SearchAnalyticsAPI of GoogleSearchConsole
     * 
     * @param array  $params Search params
     * @param string $error  Return comment message
     * 
     * @return Object|string|null
     */
    public function getSearchAnalytics($params, &$error)
    {
        if (!$this->isAuthorized) {
            return;
        }
        if ($params && isset($params['siteUrl']) && isset($params['startDate']) && isset($params['endDate'])) {
            try {
                $searchanalytics = $this->serviceWebmasters->searchanalytics;
                $request = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();

                $request->setStartDate($params['startDate']);
                $request->setEndDate($params['endDate']);

                // set default = query
                $params['dimensions'] = $params['dimensions'] ?: [0];
                if (isset($params['dimensions'])) {
                    foreach ($params['dimensions'] as $d) {
                        $dimensions[] = self::DIMENSION_OPTIONS[$d];
                    }
                    $request->setDimensions($dimensions);
                }

                // default = empty (based on API document)
                if (isset($params['dimensionFilterGroups'])) {
                    $dimensionFilterGroups = [];
                    if (isset($params['dimensionFilterGroups']['filters'])) {
                        $_dimensionList = $params['dimensionFilterGroups']['filters']['dimension'];
                        $_operatorList = $params['dimensionFilterGroups']['filters']['operator'];
                        $_expressionList = $params['dimensionFilterGroups']['filters']['expression'];
                        for ($i = 0; $i < count($_dimensionList); $i++) {
                            $_filter = [];
                            $_filter['dimension'] = self::DIMENSION_OPTIONS[$_dimensionList[$i]];
                            $_filter['operator'] = self::OPERATOR_OPTIONS[$_operatorList[$i]];
                            $_filter['expression'] = $_expressionList[$i];
                            $dimensionFilterGroups[0]->filters[] = (object) $_filter;
                        }
                    }
                    $request->setDimensionFilterGroups($dimensionFilterGroups);
                }

                // default = web (based on API document)
                if (isset($params['searchType']) && self::SEARCH_TYPE_OPTIONS[$params['searchType']]) {
                    $request->setSearchType(self::SEARCH_TYPE_OPTIONS[$params['searchType']]);
                }

                // default = auto (based on API document)
                if (isset($params['aggregationType']) && self::AGGREGATION_TYPE_OPTIONS[$params['aggregationType']]) {
                    $request->setAggregationType(self::AGGREGATION_TYPE_OPTIONS[$params['aggregationType']]);
                }

                // default = 1000 (based on API document)
                if (isset($params['rowLimit']) && $params['rowLimit']) {
                    $request->setRowLimit($params['rowLimit']);
                }

                // default = 0 (based on API document)
                if (isset($params['startRow']) && $params['startRow']) {
                    $request->setStartRow($params['startRow']);
                }

                $this->saveSearchParams($params);
                return $searchanalytics->query($params['siteUrl'], $request);
            } catch (\Google_Service_Exception $ex) {
                $error = json_decode($ex->getMessage());
                $error = $error->error->message;
            } catch (\Google_Exception $ex) {
                $error = $ex->getMessage();
            }
        }
        return;
    }

    /**
     * Save search parameters into session
     * 
     * @param array $params Search params
     * 
     * @return null
     */
    public function saveSearchParams($params)
    {
        $this->session->write('search_params', $params);
    }

    /**
     * Get search parameters from session
     * 
     * @return Array
     */
    public function getSearchParams()
    {
        return $this->session->read('search_params');
    }

    /**
     * Clear search parameters in session
     * 
     * @return null
     */
    public function clearSearchParams()
    {
        $this->session->delete('search_params');
    }

    /**
     * Get search type options
     * 
     * @return Array
     */
    public function getSearchTypeOptions()
    {
        return self::SEARCH_TYPE_OPTIONS;
    }

    /**
     * Get aggregation type options
     * 
     * @return Array
     */
    public function getAggregationTypeOptions()
    {
        return self::AGGREGATION_TYPE_OPTIONS;
    }

    /**
     * Get dimensions type options
     * 
     * @return Array
     */
    public function getDimensionsOptions()
    {
        return self::DIMENSION_OPTIONS;
    }

    /**
     * Get operator options
     * 
     * @return Array
     */
    public function getOperatorOptions()
    {
        return self::OPERATOR_OPTIONS;
    }
}
