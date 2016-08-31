====================================
Contao Extension "tablelookupwizard"
====================================

Version 3.2.1 (2016-08-31)
--------------------------
- Changed the displayed message if no search has been performed


Version 3.2.0 (2016-04-13)
--------------------------
- Added support for custom labels
- Added support for Contao 4
- Made the table 100% wide


Version 3.1.0 (2015-06-09)
--------------------------
- Added support for ORDER BY
- Added support for manual drag n drop sorting


Version 3.0.0 (2015-06-04)
--------------------------
- Added support for JOINs
- Added support for GROUP BY
- Using templates
- Cleaned up code


Version 2.0.1 (2013-11-18)
--------------------------
- Fixed Content-Length header was not calculated correctly


Version 2.0.0 (2013-11-07)
--------------------------
- Compatibility with 3.2 only (thus removed legacy code)
- JavaScript fallback can now be disabled
- Improved wizard styling


Version 1.3.5 (2013-08-13)
--------------------------
- Contao 3 compat

Version 1.3.4 (2012-09-10)
--------------------------
- Fixed wizard did not work correctly with compressed markup

Version 1.3.3 (2012-06-28)
--------------------------
- Fixed wizard did not update the values correctly

Version 1.3.2 (2012-04-03)
--------------------------
- Fixed javascript error in IE7
- Wizard did not work with Contao 2.9

Version 1.3.1 (2012-03-19)
--------------------------
- Fixed compatibility with Contao 2.11

Version 1.3.0 (2012-01-02)
--------------------------
- Added support for multiple TableLookupWizards on the same page/DCA (Ticket #527)
- Added 300ms timeout before sending ajax request for better performance
- Added eval property "matchAllKeywords" to enable boolean AND instead of OR search
- Fixed issues when using multiple output buffers (Ticket #526)
- Removed unnessesary references to the old ajax implementation

Version 1.2.0 (2011-08-28)
--------------------------
- Added support for Contao 2.10
- No longer using frontend ajax.php on a backend widget

Version 1.1.2 (2011-02-14)
--------------------------
- Added "remove selection" option for radio buttons
- Load language file for foreign table
- Updated copyright notice

Version 1.1.1 (2010-12-20)
--------------------------
- Fixed bug when lookup up multiple keywords
- Fixed bug in mandatory check with radio options

Version 1.1.0 (2010-09-26)
--------------------------
- Added support for field type "radio" or "checkbox". You must now set this value (eval->fieldTyp) in DCA (like for pageTree/fileTree)!
- Fixed minor issues and label formatting

Version 1.0.0 (2010-08-11)
--------------------------
- Initial release
