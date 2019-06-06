# PWTC Members
This is a Wordpress plugin used to provide additional shortcodes, admin pages and customizations to enhance the WooCommerce membership plugins for the [Portland Bicycling Club](https://portlandbicyclingclub.com).

## Installation
Download this distribution as a zip file, login to the portlandbicyclingclub.com Wordpress website as admin and upload this zip file as a new plugin. This plugin will be named **PWTC Members**, activate it from the Plugins management page.

### Plugin Uninstall
Deactivate and then delete the **PWTC Members** plugin from the Plugins management page.

## Plugin Shortcodes
These shortcodes allow users to add plugin specific content into Wordpress
pages.
`[pwtc_member_directory]` *form that allow search and access of the membership directory*

Argument|Description|Values|Default
--------|-----------|------|-------
limit|limit the number of members shown per page|(number)|0 (unlimited)
privacy|specify the member's privacy mode|off, exclude, hide|off

`[pwtc_member_statistics]` *lists the membership statistics*

`[pwtc_member_families]` *lists the family membership statistics*

`[pwtc_member_new_members]` *lists the names of new members*

Argument|Description|Values|Default
--------|-----------|------|-------
lookback|the number of months to look back for new members|(number)|0

`[pwtc_member_renew_nag]` *nags the logged in user to renew their membership if expired*

Argument|Description|Values|Default
--------|-----------|------|-------
renewonly|display nag only if membership is expired|no, yes|no

`[pwtc_member_accept_release]` *nags the logged in user to accept the club's release statement*

`[pwtc_member_leader_contact]` *form that allow the logged in user to set their ride leader contact information*

## Member Tools Admin Pages
This menu group is added to the Wordpress admin menu bar. Users with the **administrator** roles will have the right to access these menu pages.
### Lookup Users
Use this page to lookup user accounts by their first/last name, email or rider ID.
### Export Users
Use this page to show or export (as a CSV file) user accounts selected by a query criteria.
### Multiple Memberships
Use this page to detect all user accounts that have multiple memberships. Any such accounts should be corrected by the membership secretary to have only one membership.
### Invalid Membership Roles
Use this page to detect any user accounts that do not have a membership but are erroneously marked with the Current Member or Expired Member role. If any are found, you are given the option to fix these records.
### Missing Membership Roles
Use this page to detect any user accounts that have a membership but are missing the proper Current Member or Expired Member role. If any are found, you are given the option to fix these records.
### Test Confirmation Email
Use this page to test the membership confirmation email mechanism.

## WooCommerce Membership Customizations