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
 * Class containing data for my overview block.
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
use core_completion\progress;

require_once($CFG->libdir . '/completionlib.php');

/**
 * Class containing data for my overview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $DB;

        $courses = enrol_get_my_courses('*', 'fullname ASC');
        
        $myPinnedCourses = $DB->get_records('block_gu_dashboard_pinned', Array('userid'=>$USER->id));
        $pinnedCourses = Array();
        foreach($myPinnedCourses as $pc) {
            $pinnedCourses[$pc->courseid] = $pc->courseid;
        }
        
        $coursespinned = [];
        $coursesprogress = [];

        foreach ($courses as $course) {
            
            if(isset($pinnedCourses[$course->id])) {
                $coursespinned[] = $course;
                $course->pinned = true;
            } else {
                $course->pinned= false;
            }

            $completion = new \completion_info($course);

            // First, let's make sure completion is enabled.
            if (!$completion->is_enabled()) {
                continue;
            }

            $percentage = progress::get_course_progress_percentage($course);
            if (!is_null($percentage)) {
                $percentage = floor($percentage);
            }

            $coursesprogress[$course->id]['completed'] = $completion->is_course_complete($USER->id);
            $coursesprogress[$course->id]['progress'] = $percentage;
            
            
        }

        $coursesview = new courses_view($courses, $coursesprogress);
        $pinnedview = new courses_view($coursespinned, $coursesprogress);
        $nocoursesurl = $output->image_url('courses', 'block_gu_dashboard')->out();
        $noeventsurl = $output->image_url('activities', 'block_gu_dashboard')->out();

        return [
            'midnight' => usergetmidnight(time()),
            'coursesview' => $coursesview->export_for_template($output),
            'pinnedview' => $pinnedview->export_for_template($output),
            'urls' => [
                'nocourses' => $nocoursesurl,
                'noevents' => $noeventsurl
            ]
        ];
    }
}