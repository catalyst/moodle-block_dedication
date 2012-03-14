/**
 * Dedication block definition
 *
 * @package    block
 * @subpackage dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

This block allows to see the dedication estimated time to a Moodle course by
the participants of the course.

Session: set of two or more consecutive clicks in which the elapsed time
between every pair of consecutive clicks does not overcome an established maximum time.

Session duration: elapsed time between the first and the last click of the session

With this block there can be obtained the total dedication (sum of the duration
of every session) of all the participants of the course and the detailed dedication
of a participant (duration of each one of his sessions). The total dedication can
be downloaded in excel format. The block cannot be used in the site page, only in
courses pages.

The capability To see course dedication is added and by default only administrator
role has this capability allowed. Besides the course participants will be those
users in a role (different to administrator and course creator) in the course
context that allows them to see the course.

All texts in English, Español-Internacional (es), Portugues-Brasil (pt_br) and Deutsch (de).