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
 * The main mod_coursecertificate configuration form.
 *
 * @package     mod_coursecertificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_certificate\permission;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_coursecertificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_coursecertificate_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('hidden', 'hasissues', $this->has_issues());
        $mform->setType('hasissues', PARAM_TEXT);

        $mform->addElement('select', 'template', 'Template', $this->get_templateselect_options());
        $mform->addHelpButton('template', 'template', 'mod_coursecertificate');
        $mform->addRule('template', get_string('required'), 'required', null);
        $mform->disabledIf('template', 'hasissues', 'neq', 0);

        $mform->addElement('header', 'whenavailable', get_string('whenavailable', 'coursecertificate'));
        $mform->setExpanded('whenavailable');
        $mform->addElement('advcheckbox', 'userscanpreview', get_string('userscanpreview', 'coursecertificate'));
        $mform->addElement('advcheckbox', 'includepdf', get_string('includepdf', 'coursecertificate'));
        $mform->addElement('advcheckbox', 'emailteachers', get_string('emailteachers', 'coursecertificate'));

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Enforce validation rules here
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {

    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
    }


    /**
     * Gets array options of available templates for the user.
     *
     * @return array
     */
    private function get_templateselect_options(): array {
        global $DB;

        if (!class_exists('\\tool_certificate\\permission')) {
            throw new \coding_exception('\\tool_certificate\\permission class does not exists');
        }
        if (!$visiblecategoriescontexts = permission::get_visible_categories_contexts()) {
            // TODO: Empty select options? Show alert?
            return [];
        }
        list($sql, $params) = $DB->get_in_or_equal($visiblecategoriescontexts, SQL_PARAMS_NAMED);
        $query = "SELECT *
            FROM {tool_certificate_templates}
           WHERE contextid " . $sql;
        $records = $DB->get_records_sql($query, $params);
        $templates = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                $templates[$record->id] = format_string($record->name, true, ['context' => \context_system::instance(),
                    'escape' => false]);;
            }
        }
        return $templates;
    }

    /**
     * Returns "1" if course certificate has been issued.
     *
     * @return string
     */
    private function has_issues(): string
    {
        global $DB;

        if ($instance = $this->get_instance()) {
            $certificate = $certificate = $DB->get_record('coursecertificate', ['id' => $instance], '*', MUST_EXIST);
            $courseissues = \tool_certificate\certificate::count_issues_for_course($certificate->template, $certificate->course);
            if ($courseissues > 0) {
                return  "1";
            }
        }
        return "0";
    }
}