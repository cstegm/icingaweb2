<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Application\Benchmark;
use \Icinga\Data\Db\Query;
use \Icinga\File\Csv;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Web\Hook;
use \Icinga\Web\Widget\Tabextension\DashboardAction;
use \Icinga\Web\Widget\Tabextension\OutputFormat;
use \Icinga\Web\Widget\Tabs;
use \Icinga\Module\Monitoring\Backend;
use \Icinga\Web\Widget\SortBox;
use \Icinga\Application\Config as IcingaConfig;

class Monitoring_ListController extends ActionController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;
    /**
     * Compact layout name
     *
     * Set to a string containing the compact layout name to use when
     * 'compact' is set as the layout parameter, otherwise null
     *
     * @var string
     */
    private $compactView;

    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $this->view->grapher = Hook::get('grapher');
        $this->createTabs();
        $this->view->activeRowHref = $this->getParam('detail');
    }

    /**
     * Overwrite the backend to use (used for testing)
     *
     * @param Backend $backend      The Backend that should be used for querying
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Display host list
     */
    public function hostsAction()
    {
        Benchmark::measure("hostsAction::query()");
        $this->compactView = "hosts-compact";
        $this->view->hosts = $this->query(
            'status',
            array(
                'host_icon_image',
                'host_name',
                'host_state',
                'host_address',
                'host_acknowledged',
                'host_output',
                'host_long_output',
                'host_in_downtime',
                'host_is_flapping',
                'host_state_type',
                'host_handled',
                'host_last_check',
                'host_last_state_change',
                'host_notifications_enabled',
                'host_unhandled_service_count',
                'host_action_url',
                'host_notes_url',
                'host_last_comment'
            )
        );
        $this->setupSortControl(array(
            'host_last_check'   => 'Last Host Check',
            'host_severity'     => 'Host Severity',
            'host_state'        => 'Current Host State',
            'host_name'         => 'Host Name',
            'host_address'      => 'Address',
            'host_state'        => 'Hard State'
        ));
    }

    /**
     * Display service list
     */
    public function servicesAction()
    {
        $this->compactView = "services-compact";

        $this->view->services = $this->query('status', array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state' => 'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_last_state_change' => 'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_last_comment'
        ));

        $this->setupSortControl(array(
            'service_last_check'    =>  'Last Service Check',
            'service_severity'      =>  'Severity',
            'service_state'         =>  'Current Service State',
            'service_description'   =>  'Service Name',
            'service_state_type'    =>  'Hard State',
            'host_severity'         =>  'Host Severity',
            'host_state'            =>  'Current Host State',
            'host_name'             =>  'Host Name',
            'host_address'          =>  'Host Address',
            'host_last_check'       =>  'Last Host Check'
         ));
    }

    /**
     * Fetch the current downtimes and put them into the view property `downtimes`
     */
    public function downtimesAction()
    {
        $this->setDefaultSortColumn('downtime_is_in_effect');
        $this->view->downtimes = $this->query('downtime', array(
            'host_name',
            'object_type',
            'service_description',
            'downtime_entry_time',
            'downtime_internal_downtime_id',
            'downtime_author_name',
            'downtime_comment_data',
            'downtime_duration',
            'downtime_scheduled_start_time',
            'downtime_scheduled_end_time',
            'downtime_is_fixed',
            'downtime_is_in_effect',
            'downtime_triggered_by_id',
            'downtime_trigger_time'
        ));

        $this->setupSortControl(array(
            'downtime_is_in_effect'         => 'Is In Effect',
            'object_type'                   => 'Service/Host',
            'host_name'                     => 'Host Name',
            'service_description'           => 'Service Name',
            'downtime_entry_time'           => 'Entry Time',
            'downtime_author_name'          => 'Author',
            'downtime_comment_data'         => 'Comment',
            'downtime_scheduled_start_time' => 'Start',
            'downtime_scheduled_end_time'   => 'End',
            'downtime_trigger_time'         => 'Trigger Time',
            'downtime_internal_downtime_id' => 'Downtime ID',
            'downtime_duration'             => 'Duration',
         ));
    }

    /**
     * Display notification overview
     */
    public function notificationsAction()
    {
        $this->setDefaultSortColumn('notification_start_time', 'DESC');
        $this->view->notifications = $this->query(
            'notification',
            array(
                'host_name',
                'service_description',
                'notification_type',
                'notification_reason',
                'notification_start_time',
                'notification_contact',
                'notification_information',
                'notification_command'
            )
        );
        $this->setupSortControl(array(
            'notification_start_time'   => 'Notification Start'
        ));
    }

    /**
     * Create query
     *
     * @param string $view
     * @param array  $columns
     *
     * @return Query
     */
    private function query($view, $columns)
    {
        $extra = preg_split(
            '~,~',
            $this->_getParam('extracolumns', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        if (empty($extra)) {
            $cols = $columns;
        } else {
            $cols = array_merge($columns, $extra);
        }
        $this->view->extraColumns = $extra;
        $query = $this->backend->select()
            ->from($view, $cols)
            ->applyRequest($this->_request);
        $this->handleFormatRequest($query);
        return $query->paginate();
    }

    /**
     * Handle the 'format' and 'view' parameter
     *
     * @param Query $query The current query
     */
    private function handleFormatRequest($query)
    {
        if ($this->compactView !== null && ($this->_getParam('view', false) === 'compact')) {
            $this->_helper->viewRenderer($this->compactView);
        }

        if ($this->_getParam('format') === 'sql'
            && IcingaConfig::app()->global->get('environment', 'production') === 'development') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->getQuery()->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json')
        {
            header('Content-type: application/json');
            echo json_encode($query->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }

    /**
     * Set the default sort column for this action if none is provided
     *
     * @param string $column    The column to use for sorting
     * @param string $dir       The direction ('ASC' or 'DESC')
     */
    private function setDefaultSortColumn($column, $dir = 'DESC')
    {

        $this->setParam('sort', $this->getParam('sort', $column));
        $this->setParam('dir', $this->getParam('dir', $dir));
    }

    /**
     * Create a sort control box at the 'sortControl' view parameter
     *
     * @param array $columns    An array containing the sort columns, with the
     *                          submit value as the key and the value as the label
     */
    private function setupSortControl(array $columns)
    {
        $this->view->sortControl = new SortBox(
            $this->getRequest()->getActionName(),
            $columns
        );
        $this->view->sortControl->applyRequest($this->getRequest());
    }

    /**
     * Return all tabs for this controller
     *
     * @return Tabs
     */
    private function createTabs()
    {

        $tabs = $this->getTabs();
        $tabs->extend(new OutputFormat())
            ->extend(new DashboardAction());
    }
}
// @codingStandardsIgnoreEnd
