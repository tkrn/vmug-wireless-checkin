1. Video Demonstration
	- http://youtu.be/3kKPiB_Ji2I
	- http://youtu.be/nMdMsppRarM
  
2. Quick Summary
	- Wireless checkin application developed for the Cleveland VMUG Group.
  
3. Bullet Point Facts and Instructions
	- Uses SQLite as the database backend
	- Checkin.db (SQLite database) needs to have 664 Unix permissions
	- Set variables in settings.php
	- Requires PHP5 with the SQLite library.
	- Import Data requires a particular schema, see below.
	- Import Data Format: Excel 97-2003 Workbook (xls) or Excel Workbook (xlsx)
	- Import Data: Excel must match the expected schema input with no headings: 
		Column A (Last Name), Column B (First Name), Column C (Company) 
		
4. Questions
	- Post any questions, comments, bug reports to the blog article below.

5. Change Log
	- Release 1.01 (10/15/2014)
		1. Hash for the pdf file name in the tmp directory.
		2. Placed pdf label creation and print towards end of index.php for end user performance
		   improvement.

	- Initial 1.0 (09/24/2014)
	
6. Other
	- Author: Patrick Stasko
	- Website: http://lvlnrd.com
	- Blog Article: http://lvlnrd.com/raspberry-pi-edimax-wifi-dymo-printer-checkin-process-tutorial
	- Inital Release Date: 09/24/2014