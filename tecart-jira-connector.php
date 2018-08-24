<?php
namespace Grav\Plugin;

use Grav\Common\Data\Data;
use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Plugin\GitSync\Helper;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\TecartJiraConnector\Jira;

class TecartJiraConnectorPlugin extends Plugin
{
    protected $route = 'tecart-jira-connector';
    protected $controller;
    protected $git;

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized'      => ['onPluginsInitialized', 20],
            'onAdminTwigTemplatePaths'  => ['onAdminTwigTemplatePaths', 20],
            'onGetPageTemplates'        => ['onGetPageTemplates', 20]
        ];
    }

    /**
     * Add custom admin panel templates
     */
    public function onAdminTwigTemplatePaths($event)
    {
        $event['paths'] = [__DIR__ . '/admin/themes/grav/templates'];
    }

    public function onGetPageTemplates(Event $event)
    {
        $types = $event->types;
        $locator = Grav::instance()['locator'];
        $types->scanBlueprints($locator->findResource('plugin://' . $this->name . '/blueprints'));
    }

    public function onAdminCreatePageFrontmatter(Event $event) {

        $header = $event['header'];
        $param  = $event['data'];

        // Set Jira Variables
        $jira_url     = $this->config->get('plugins.tecart-jira-connector.jira_url');
        $jira_user    = $this->config->get('plugins.tecart-jira-connector.jira_user');
        $enc_password = $this->config->get('plugins.tecart-jira-connector.jira_password');

        $password     = GitHelper::decrypt($enc_password);
        $userpwd      = $jira_user . ":" . $password;

        $issues       = isset($this->grav['twig']->issues) ? $this->grav['twig']->issues : null;
        $issue_id     = isset($param['issue_id']) ? $param['issue_id'] : null;

        $user         = $this->grav['user']['username'];
        $userfull     = $this->grav['user']['fullname'];
        
        // Save Issue Summary
        if ($issues && $issue_id) {
            foreach ($issues as $issue ) 
                if ($issue['key'] == $issue_id) 
                    $header['draft']['issue']['summary'] = $issue['fields']['summary'];
        }

        // Write page data
        $event['header'] = $header;

        // Set Twig Transition Vaiable
        $this->grav['twig']->taw_transition = isset($transition) ? $transition : false;
    }

    /**
     * Enable only if url matches to the configuration.
     */
    public function onPluginsInitialized()
    {
        $this->grav['locator']->addPath('blueprints', '', __DIR__ . DS . 'blueprints');

        require_once __DIR__ . '/vendor/autoload.php';

        if (!$this->isAdmin()) {
            return;
        } else {
            $this->enable([
                'onAdminSave'            => ['onAdminSave', 20]
            ]);
        }

        if ($this->config->get("plugins.git-sync.enabled") == true)
        {
            $uri = $this->grav['uri'];

            if (isset($uri->paths()[1]) && $uri->paths()[1] === 'pages')
            {
                $jira_url = $this->config->get('plugins.tecart-jira-connector.jira_url');
                $jira_project = $this->config->get('plugins.tecart-jira-connector.jira_project');
                $jira_user = $this->config->get('plugins.tecart-jira-connector.jira_user');
                $enc_password = $this->config->get('plugins.tecart-jira-connector.jira_password');

                $password = Helper::decrypt($enc_password);
                $userpwd = $jira_user . ":" . $password;
                $user = $this->grav['user']['username'];
        
                // Get issue list
                $url = $jira_url . "/rest/api/2/search";
                $data = array("jql" => "project='".$jira_project."'", "fields" => ["id","key","summary","assignee", "status"]);
                $httpHeader = array(
                    'Accept: application/json',
                    'Content-Type: application/json'
                );

                try {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
                        $result = curl_exec($ch);
                        $ch_error = curl_error($ch);
                        if ($ch_error == '') 
                            $out = $result;
                        else
                            $out = json_encode(array('error' => $ch_error));
                
                } catch(Exception $e){
                    $out = json_encode(array('error' => $e->getMessage()));
                }
                
                $issues = json_decode($out, true);
        
                $this->grav['twig']->issues = isset($issues["issues"]) ? $issues["issues"] : null;
                $this->grav['twig']->user = $user;
                $this->grav['twig']->jira_url = $jira_url;

                // Get user list
                $url = $jira_url . "/rest/api/2/user/assignable/search?project=DE";
                $data = array("project='".$jira_project."'");

                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_VERBOSE, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
                    $result = curl_exec($ch);
                    $ch_error = curl_error($ch);
                    if ($ch_error == '') 
                        $out = $result;
                    else
                        $out = json_encode(array('error' => $ch_error));
            
                } catch(Exception $e){
                    $out = json_encode(array('error' => $e->getMessage()));
                }
                curl_close($ch); 

                $jira_users = json_decode($out, true);
                $this->grav['twig']->jira_users = isset($jira_users) ? $jira_users : null;
            }
        }
    }

    public function onAdminSave($event)
    {
        $obj           = $event['object'];
        $data          = $event['data'];
        $isPluginRoute = $this->grav['uri']->path() == '/admin/plugins/' . $this->name;

        if ($obj instanceof Data) {
            if (!$isPluginRoute || !Helper::isGitInstalled()) {
                return true;
            } else {
                // empty password, keep current one or encrypt if haven't already
                $password = $obj->get('jira_password', false);
                if (!$password) { // set to !()
                    $current_password = $this->config->get('plugins.tecart-jira-connector.jira_password');
                    // password exists but was never encrypted
                    if (substr($current_password, 0, 8) !== 'gitsync-') {
                        $current_password = Helper::encrypt($password);
                    }
                } else {
                    // password is getting changed
                    $current_password = Helper::encrypt($password);
                }
                $obj->set('jira_password', $current_password);
            }
        }

        $uri = $this->grav['uri'];

        if (isset($uri->paths()[2]) && $uri->paths()[1] === 'pages')
        {
            $path           = $obj->path();
            $route          = $obj->route();
            $issues         = isset($this->grav['twig']->issues)            ? $this->grav['twig']->issues           : null;
            $issue_id       = isset($data['issue_id'])                      ? $data['issue_id']                     : "";
            $issue_id       = isset($obj->header()->draft['issue']['id'])   ? $obj->header()->draft['issue']['id']  : $issue_id;
            $transition     = isset($data['fireTransition'])                ? $data['fireTransition']               : "save";

            // Get Issue Summary
            $issue_summary = '';
            if ($issues && $issue_id) {
                foreach ($issues as $issue ) 
                    if ($issue['key'] == $issue_id) 
                        $issue_summary = $issue['fields']['summary'];
            }

            $commit_message = $issue_id . ": " . $issue_summary . " (" . $transition . " page:/" . $route . ")";

            $this->grav['twig']->commit_message = isset($commit_message) ? $commit_message : 'Grav content update';
            $this->grav['twig']->commit_path = isset($path) ? $path : null;
        }

        return $obj;
    }
}
