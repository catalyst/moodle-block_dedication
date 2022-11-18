# Introduction

This block allows to see the dedication estimated time to a Moodle course by the participants of the course.
https://moodle.org/plugins/block_dedication

# MOODLE 4.X updates
Maintenance for this plugin has been taken over by Catalyst IT thanks to funding from the University of Canterbury.

## This is Work in Progress code under development - use at your own risk, see some of the open issues in the tracker for known issues.

NOTE: This new version of the block differs from the original version in some significant ways:
1. To unify global reporting across all courses, many settings previously set within the block are now "site-level" settings, instead of being able to customise at the course level within the block settings.
2. As teachers can access reports using normal report navigation, it assumes if you have added the block to a course, you want students to see the dedication data - this differs to previous versions of the block that allowed you to enable/disable student access as a setting. For this reason during the upgrade process the code will find all existing blocks that are set to not allow student access and flag them as hidden to students - allowing the teacher/admin to delete the block or configure the view permissions on the block as required.
3. Timespent information is now generated via a scheduled task - this improves general performance but also enables the data to be exposed within Moodle's new Report Builder custom reporting.

# Branches

| Moodle version    | Branch             | Status |
| ----------------- | ------------------ | ------------------ |
| Moodle 4.0+       | `MOODLE_400_STABLE` | Work in progress |
| Moodle 3.0 - 3.11 | `MOODLE_30_STABLE` | Old unsupported branch - pull requests welcome |

# How dedication time is estimated?
Time is estimated based in the concepts of Session and Session duration applied
to Moodle's log entries:

  Click:
  every time that a user access to a page in Moodle a log entry is stored.

  Session:
  set of two or more consecutive clicks in which the elapsed time between every
  pair of consecutive clicks does not overcome an established maximum time.

  Session duration:
  elapsed time between the first and the last click of the session.

# Features

This block is intended to be used only by teachers, so students aren't going to
see it and their dedication time. However, block can be configured to show
dedication time to students too.

Teachers can use a tool to analyze dedication time within a course. The tool
provides three views:

  Dedication time of the course:
  calculates total dedication time, mean dedication time and connections per day
  for each student.

  Dedication time of a group:
  the same but only for choosed group members.

  Dedication of a student:
  detalied sessions for a student with start date & time, duration and ip.

The tools provide an option to download all data in spreadsheet format. The use
of this tool is restricted by a capability to teachers and admins only.

This block cannot be used in the site page, only in courses pages.

All texts in English and Spanish.

# Support
Please use the moodle coummunity forums for help with this plugin:
https://moodle.org/mod/forum/view.php?id=44

Alternatively commercial-level support is available from Catalyst IT:
https://www.catalyst.net.nz/

# Credits

This block was previously developed and produced by Aday Talavera, CICEI at Las Palmas de Gran Canaria University and the first version for Moodle 1.9 was developed by Borja Rubio Reyes.
