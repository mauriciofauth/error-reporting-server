<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Stats Shell.
 *
 * phpMyAdmin Error reporting server
 * Copyright (c) phpMyAdmin project (https://www.phpmyadmin.net/)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) phpMyAdmin project (https://www.phpmyadmin.net/)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 *
 * @see      https://www.phpmyadmin.net/
 */

namespace App\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;

/**
 * Stats shell.
 */
class StatsShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Incidents');
        $this->loadModel('Reports');
    }

    public function main()
    {
        foreach ($this->Incidents->filterTimes as $filter_string => $filter) {
            foreach ($this->Incidents->summarizableFields as $field) {
                $this->out('processing ' . $filter_string . ':' . $field);
                $entriesWithCount = $this->Reports->
                        getRelatedByField($field, 25, false, false, $filter['limit']);
                $entriesWithCount = json_encode($entriesWithCount);
                Cache::write($field . '_' . $filter_string, $entriesWithCount);
            }
            $query = array(
                'group' => 'grouped_by',
                'order' => 'Incidents.created',
            );
            if (isset($filter['limit'])) {
                $query['conditions'] = array(
                    'Incidents.created >=' => $filter['limit'],
                );
            }
            $downloadStats = $this->Incidents->find('all', $query);
            $downloadStats->select(array(
                'grouped_by' => $filter['group'],
                'date' => "DATE_FORMAT(Incidents.created, '%a %b %d %Y %T')",
                'count' => $downloadStats->func()->count('*'),
            ));
            $downloadStats = json_encode($downloadStats->toArray());
            Cache::write('downloadStats_' . $filter_string, $downloadStats);
        }
    }
}
