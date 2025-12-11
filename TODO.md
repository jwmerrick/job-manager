# ROADMAP

## v0.8.4 -> Branch `feature/preview`
Implementing a job list and job post preview function.
Will be especially helpful in figuring out if you've got the template setup right.

Complete:
[X] When accessing Job List Frontend, "Attempt to read property ID on null in frontend-shortcodes on Line 90, 92 for /jobs/
[X] Do not flush rewrite rules on every page load
[X] Fix Notifications (add / delete / archive) to use core functionality
[X] On admin-interviews, get call to undefined function cal_days_in_month -> Requires PHP calendar extension
### Post Status
[X] Set jobs to publish in future with status "Future'.  WP will change them to "Publish" automatically.
[X] Add custom post status "Archive" for jobs marked as archive.
[X] New jobs can be saved as "Draft".  When editing a new job, instead of "Save", will also have "Preview" -> Creates post as draft and redirects to preview.

To-Do:
[ ] Use of deprecated get_currentuserinfo() in admin-comments.php, admin-emails.php, frontend-application.php, frontend-user.php
[ ] Break out HTML for admin job edit form into seperate view `admin-view-edit-job.php`

## v0.8.5
[ ] RSS Endpoint, Enable / Disable in Admin settings, shortcode for RSS (It's in the code, but not documented)
[ ] Deactivate should remove or make "Draft" the Job Manager main page

## v0.9.0 ->
Add Help Pages to Admin
Add `delete_plugin` functionality

### Post Status
[ ] Need database update to change status to "Archive" for those that are in the past or are "Draft"
    -> This will require going from database V19 to database V20 see `setup.php`
[ ] New database version... Add to `update` to modify the post status for jobs:
    -> Change `draft` to `jobman_archive`
    -> Change `publish` where `displayenddate` is in the past to `jobman_expired`
        -> this can be done with `jobman_update_post_statuses()`

## v1.0.0 -> 
Add REST endpoint for integration with OpenCATS
Strip out Thomas Townsend / wp-job-manager.com references and links if Thomas no longer wishes to participate