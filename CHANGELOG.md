====================================
Contao Extension "tablelookupwizard"
====================================

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