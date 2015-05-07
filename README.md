1. Video Demonstration
	- http://youtu.be/3kKPiB_Ji2I
	- http://youtu.be/nMdMsppRarM

  
2. Quick Summary
	- Wireless checkin application developed for the Cleveland VMUG Group.

  
3. Bullet Point Facts and Instructions
	- Uses SQLite as the database backend
	- database.db (SQLite database) needs to have 664 Unix permissions
	- Set variables in settings.php
	- Requires PHP5 with the SQLite library.
	- Import Data requires a particular schema, see below.
	- Import Data Format: Excel 97-2003 Workbook (xls) or Excel Workbook (xlsx)
	- Import Data: Excel must match the expected schema input with no headings:
	
		Column A (Last Name), Column B (First Name), Column C (Company), Column D (Company Type)

		
4. Questions
	- Post any questions, comments, bug reports to the blog article below.


5. Change Log

	- Release 1.04 (05/07/2015)
	    1. Rewrote the database structure. New views, re-written table.
		2. Removed extra truncate functions for old style database structure.
		3. Modified the index.php to take advantage of the new database structure.
		4. Removed duplicate code in index.php
		5. Added Customer, Partner, Vendor (Customer Type) fields.
		6. Updated jQuery, jQuery Validate and jQuery Notification Plugins.
		7. CSS width and alignment of elements. Validation CSS/messages modified.

    - Release 1.03 (11/22/2014)
		1. Added views in SQLite database for random user selection.
		2. Added random.php page for random registered user selection.
		
	- Release 1.02 (10/17/2014)
		1. Changed registration mode to print towards end of index.php as well. 
		   Left out in version 1.01
		
	- Release 1.01 (10/15/2014)
		1. Hash for the pdf file name in the tmp directory.
		2. Placed pdf label creation and print towards end of index.php for end user performance
		   improvement.

	- Initial 1.0 (09/24/2014)

	
6. Known Issues

	- Release 1.04 (05/07/2015)
		1. Administration function has issues importing particular XLS formats. MIME Type? 
		   Hard fault on admin.php. Copy-paste data into a new XLSX document and save to first sheet. 
		   Additional PHP logic is needed.

		   
7. Other
	- Author: Patrick Stasko
	- Website: http://lvlnrd.com
	- Blog Article: http://lvlnrd.com/raspberry-pi-edimax-wifi-dymo-printer-checkin-process-tutorial
	- Git Repository: https://bitbucket.org/lvlnrd/vmug-wireless-checkin 
	- Initial Release Date: 09/24/2014
		   