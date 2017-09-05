<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class containing data for courses view in the myoverview block.
 *
 * @package    block_gu_dashboard
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_gu_dashboard\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use core_course\external\course_summary_exporter;

/**
 * Class containing data for courses view in the myoverview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pinned_courses implements renderable, templatable {
    /** Quantity of courses per page. */
    const COURSES_PER_PAGE = 6;

    /** @var array $courses List of courses the user is enrolled in. */
    protected $courses = [];

    /** @var array $coursesprogress List of progress percentage for each course. */
    protected $coursesprogress = [];

    /**
     * The courses_view constructor.
     *
     * @param array $courses list of courses.
     * @param array $coursesprogress list of courses progress.
     */
    public function __construct($courses, $coursesprogress) {
        $this->courses = $courses;
        $this->coursesprogress = $coursesprogress;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $today = time();

        // Build courses view data structure.
        $coursesview = [
            'hascourses' => !empty($this->courses)
        ];

        // How many courses we have per status?
        $coursesbystatus = ['past' => 0, 'inprogress' => 0, 'future' => 0];
        foreach ($this->courses as $course) {
            $startdate = $course->startdate;
            $enddate = $course->enddate;
            $courseid = $course->id;
            $context = \context_course::instance($courseid);
            $exporter = new course_summary_exporter($course, [
                'context' => $context
            ]);
            $exportedcourse = $exporter->export($output);
            // Convert summary to plain text.
            $exportedcourse->summary = content_to_text($exportedcourse->summary, $exportedcourse->summaryformat);

            if (isset($this->coursesprogress[$courseid])) {
                $coursecompleted = $this->coursesprogress[$courseid]['completed'];
                $courseprogress = $this->coursesprogress[$courseid]['progress'];
                $exportedcourse->hasprogress = !is_null($courseprogress);
                $exportedcourse->progress = $courseprogress;
            }

                // Courses still in progress. Either their end date is not set, or the end date is not yet past the current date.
                $inprogresspages = floor($coursesbystatus['inprogress'] / $this::COURSES_PER_PAGE);

                $coursesview['inprogress']['pages'][$inprogresspages]['courses'][] = $exportedcourse;
                $coursesview['inprogress']['pages'][$inprogresspages]['active'] = ($inprogresspages == 0 ? true : false);
                $coursesview['inprogress']['pages'][$inprogresspages]['page'] = $inprogresspages + 1;
                $coursesview['inprogress']['haspages'] = true;
                $coursesbystatus['inprogress']++;
            }
        }

        // Build courses view paging bar structure.
        foreach ($coursesbystatus as $status => $total) {
            $quantpages = ceil($total / $this::COURSES_PER_PAGE);

            if ($quantpages) {
                $coursesview[$status]['pagingbar']['disabled'] = ($quantpages <= 1);
                $coursesview[$status]['pagingbar']['pagecount'] = $quantpages;
                $coursesview[$status]['pagingbar']['first'] = ['page' => '&laquo;', 'url' => '#'];
                $coursesview[$status]['pagingbar']['last'] = ['page' => '&raquo;', 'url' => '#'];
                for ($page = 0; $page < $quantpages; $page++) {
                    $coursesview[$status]['pagingbar']['pages'][$page] = [
                        'number' => $page + 1,
                        'page' => $page + 1,
                        'url' => '#',
                        'active' => ($page == 0 ? true : false)
                    ];
                }
            }
        }

        return $coursesview;
    }
}
