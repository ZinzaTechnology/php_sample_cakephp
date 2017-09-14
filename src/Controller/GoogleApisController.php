<?php
namespace App\Controller;

use Cake\Event\Event;

class GoogleApisController extends AppController
{
    /**
     * Load GoogleAPI Component
     * 
     * @param Event $event default
     * 
     * @return null
     */
    public function beforeFilter(Event $event)
    {
        parent::initialize($event);
        $this->loadComponent('GoogleApi');
    }

    /**
     * Main view of SearchAnalytics
     * 
     * @return null
     */
    public function index()
    {
        if ($this->request->getData('Submit') !== null) {
            // Get search parameters from request
            $params['siteUrl'] = $this->request->getData('siteUrl');
            $params['startDate'] = $this->request->getData('startDate');
            $params['endDate'] = $this->request->getData('endDate');
            $params['dimensions'] = $this->request->getData('dimensions');
            $params['dimensionFilterGroups'] = $this->request->getData('dimensionFilterGroups');
            $params['searchType'] = $this->request->getData('searchType');
            $params['aggregationType'] = $this->request->getData('aggregationType');
            $params['rowLimit'] = $this->request->getData('rowLimit');
            $params['startRow'] = $this->request->getData('startRow');
        } else {
            // Or get search parameters from most recent search
            $params = $this->GoogleApi->getSearchParams();
        }

        $isAuthorized = $this->GoogleApi->isAuthorized;
        $authUrl = $this->GoogleApi->createAuthUrl();
        $userInfo = $this->GoogleApi->getUserInfo();
        $searchTypeOptions = $this->GoogleApi->getSearchTypeOptions();
        $aggregationTypeOptions = $this->GoogleApi->getAggregationTypeOptions();
        $dimensionsOptions = $this->GoogleApi->getDimensionsOptions();
        $operatorOptions = $this->GoogleApi->getOperatorOptions();
        $error = null;

        // Get data from SearchAnalytics API
        $results = $this->GoogleApi->getSearchAnalytics($params, $error);

        $this->set(
            compact(
                'authUrl',
                'isAuthorized',
                'results',
                'userInfo',
                'error',
                'params',
                'searchTypeOptions',
                'aggregationTypeOptions',
                'dimensionsOptions',
                'operatorOptions'
            )
        );
    }

    /**
     * Callback from google after authorized
     * Need to register this url in the Credentials screen
     * 
     * @return null
     */
    public function callback()
    {
        if (!$this->request->getQuery('code')) {
            $authUrl = $this->GoogleApi->createAuthUrl();
            return $this->redirect(filter_var($authUrl, FILTER_SANITIZE_URL));
        } else {
            $this->GoogleApi->authenticate($this->request->getQuery('code'));
            $this->redirect(
                [
                'controller' => 'GoogleApis',
                'action' => 'index'
                ]
            );
        }
    }

    /**
     * Revoke authorized token
     * 
     * @return null
     */
    public function revoke()
    {
        $this->GoogleApi->revokeToken();
        $this->redirect(
            [
            'controller' => 'GoogleApis',
            'action' => 'index'
            ]
        );
    }

    /**
     * Clear search parameters of SearchAnalytics
     * 
     * @return null
     */
    public function clearSearchParams()
    {
        $this->GoogleApi->clearSearchParams();
        $this->redirect(
            [
            'controller' => 'GoogleApis',
            'action' => 'index'
            ]
        );
    }

    /**
     * Get search form by ajax
     * 
     * @return null
     */
    public function searchForm()
    {
        $dimensionsOptions = $this->GoogleApi->getDimensionsOptions();
        $operatorOptions = $this->GoogleApi->getOperatorOptions();
        $this->set(
            compact(
                'dimensionsOptions',
                'operatorOptions'
            )
        );
    }
}
