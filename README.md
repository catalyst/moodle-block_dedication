# Introduction

This block allows to see the dedication estimated time to a Moodle course by the participants of the course.
https://moodle.org/plugins/block_dedication

# MOODLE 4.X updates
Maintenance for this plugin has been taken over by Catalyst IT thanks to funding from the University of Canterbury.

NOTE: This new version of the block differs from the original version in some significant ways:
1. To unify global reporting across all courses, you can no longer set the minimum session limit within the block and can only set this in the site-level settings - this fixes various inconsistency issues previously reported and makes it clearer what the setting actually does. The site-level session limit is also exposed to teachers in the reporting pages via the text "Excludes sessions less than X".
2. Timespent information is now generated via a scheduled task - this improves general performance but also enables the data to be exposed within Moodle's new Report Builder custom reporting.
3. The main course report uses Moodle's reportbuilder api, however this unfortunately drops the ability to filter the report based on the date as system level reportbuilder reports do not support aggregate values well (see MDL-76392) - You can still create a custom report within report-buidlers custom reports to do this and make it available for teachers to use.
4. Changing the site level settings (session_limit etc) does not recalculate existing records, this will be addressed in a future release - see issue #59.
5. The first time this new version is installed, only the last 12 weeks of sessions usage is calculated - if you want to calculate further historical data see the CLI script in the CLI folder (requires server-level access to execute.).
6. Users (students) can now see a link to a report that shows them a list of all ther sessions and estimated durations.
7. Custom reportbuilder source is available for site-level reporting (under admin > reports > reportbuilder > custom reports).
8. Course and user-level reporting now uses the reportbuilder api available in Moodle 4.0.

# Branches

| Moodle version    | Branch             | Status |
| ----------------- | ------------------ | ------------------ |
| Moodle 4.0+       | `MOODLE_400_STABLE` | Beta |
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
Moodle 4.0 release developed with funding thanks to Canterbury University

![UC-Logo-3-2_3654775638524282877 (1)](https://user-images.githubusercontent.com/362798/202991887-815a122e-5b1b-49f0-8546-0fed94239753.jpg)


This block was previously developed and produced by Aday Talavera, CICEI at Las Palmas de Gran Canaria University and the first version for Moodle 1.9 was developed by Borja Rubio Reyes.
