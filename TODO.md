# ROADMAP

## v0.8.4 -> Branch `feature/preview`
Implementing a job list and job post preview function.
Will be especially helpful in figuring out if you've got the template setup right.

Complete:
[X] When accessing Job List Frontend, "Attempt to read property ID on null in frontend-shortcodes on Line 90, 92 for /jobs/
[X] Do not flush rewrite rules on every page load

To-Do:
[ ] RSS Endpoint, Enable / Disable in Admin settings, shortcode for RSS (It's in the code, but not documented)
[ ] Fatal error on Admin > Interviews
[ ] Use of deprecated get_currentuserinfo() in admin-comments.php, admin-emails.php, frontend-application.php, frontend-user.php
[ ] Deactivate should remove or make "Draft" the Job Manager main page

### Post Status
The only post statuses used are "Draft" for jobs marked as archive and "Publish" for jobs that are future.  Modify the post status usage to leverage WP's built-in post functionality.
[X] Set jobs to publish in future with status "Future'.  WP will change them to "Publish" automatically.
[ ] Add custom post status "Archive" for jobs marked as archive.
[ ] New jobs can be saved as "Draft".  When editing a job, instead of "Save", will have "Preview", "Publish", "Archive"
[ ] Use post type 'revision' for preview functionality.

## v1.0.0 -> 
Add REST endpoint for integration with OpenCATS
Strip out Thomas Townsend / wp-job-manager.com references and links if Thomas no longer wishes to participate