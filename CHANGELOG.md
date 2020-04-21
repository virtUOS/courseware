CHANGELOG
=========

4.5.3
-----
* fix DownloadBlock: fix import
* fix PostBlock: fix update handler

4.5.2
-----
* fix HtmlBlock: CKEditor layout issues
* fix HtmlBlock: store without CKEditor
* fix AudioBlock: recording
* enhance PdfBlock: optional download button
* fix news: do not show hidden content
* enchance IFrameBlock: add header
* fix ForumBlock: import issue
* fix approval issues
* fix all blocks with files: do not show hidden folders
* enhance IAV-Block: disable range slider (optional)
* fix ChartBlock: naming
* fix Section: nav stuck position
* fix TestBlock: set grade 1 if no sheet is selected
* fix GalleryBlock: use more folder types
* fix PostBlock: hidden post issue
* fix block mananger: i18n issue with sem names
* enhance block mananger: add help box
* fix VideoBlock: multiple video tag issue
* fix copy empty blocks issue
* enhance FolderBlock: keep folder type and name on copy
* fix some other details 


4.5.1
-----
* select2 in KeyPointBlock
* more icons in KeyPointBlock
* fix LTI issues in OpenCastBlock
* some minor layout fixes

4.5.0
-----
* excitingly enhanced block manager build with vue.js
* User or Group can get approval to edit
* new Block: FolderBlock -> show all files in a Folder, upload is optional
* numerous layout adjustments
* update i18n
* some fixes

4.4.7
-----
* fix #185
* fix #191
* EmbedBlock -> add infoboxes, full width option for images
* Blockadder -> fix sidebar layout
* ChartBlock -> fix layout

4.4.6
-----
HOTFIX !
Fix IFrameBlock: prevent redirect

4.4.5
-----
* fix VideoBlock export and import
* upgrade block manager layout
* TestBlock: Warning for unsaved answers

4.4.4
-----
* Fixed MathJax issue (see #187)

4.4.3
-----
* enhance CanvasBlock, CodeBlock and OpenCastBlock
* add new Block: ImageMap
* Video recording for teachers
* fix GalleryBlock layout
* add new icons
* fix bugs and layout
* StudIP 4.3 ready

4.4.2
-----
* enhance TestBlock for Vips 1.5.1
* some little fixes

4.4.1
-----
* fix scrollytelling layout
* enhance CanvasBlock
* enhance BlockManager

4.4.0
-----
* New Blocks:
** Audio Gallery
** Canvas

* New Block Manager:
** sort chapters, sub-chapters, sections and blocks via drag and drop
** import from courseware in other courses
** import parts from a courseware archiv (exported courseware content)

* New Functions:
** AudioBlock: take a recording with your microphone
** TestBlock: is ready for Vips 1.5, file upload enabled
** a single block can be invisible to students
** withdraw date for chapters and sub-chapters

* and some minor layout and bug fixes

4.3.3
-----
* AssortBlock: show assorted blocks in author view
* fix favorites

4.3.2
-----
* fixing open graph issues
* use config instead of datafields for favs

4.3.1
-----
* custom icons for section nav
* fix favorites for StudIP Version 4.0.x
* open graph in LinkBlock

4.3.0
-----
* introducing scrollytelling
* new blocks for scrollytelling - DateBlock, TypewriterBlock and ScrollyBlock
* add favorites to block adder
* ready for Vips 1.4
* StudIP user files accessible
* fix some layout issues

4.2.2
-----
* fix vips sortables
* fix bug in InteractiveVideoBlock
* enhance EmbedBlock - Youtube videos may have start and end time
* fix file export
* fix vips in iav
* enhance assort block

4.2.0
-----
* fixing HTMLBlock wysiwyg author view for Stud.IP >= 4.2
* fixing date picker issue for Stud.IP >= 4.1
* add new block: DialogCardsBlock
* add new block: EmbedBlock
* add new block: InteractiveVideoBlock
* add new block: BeforeAfterBlock
* add new block: ChartBlock
* add new block: OpenCastBlock
* new block adder design
* modernize models for Stud.IP >= 4.2
* cleanup VideoBlock

4.1.0
-----
* Set seminar title
* enhance TestBlock functions
* better usability for postoverview
* fixing wrong change date
* SearchBlock: fix encoding issue 
* GalleryBlock: fix import
* fix cid in news controller
* fix progress calculation in cpo
* fix some old layout issues
* add functions for DatenschutzPlugin 

4.0.7
-----
* modernized look and feel
* enhance discussion overview and PostBlock - post can now be hidden
* improve DownloadBlock author view
* improve TestBlock - use vips.js, embed character picker, ready for vips 1.4
* remove Metrics
* fix file import
* some little layout and bug fixes

4.0.6
-----
* fixing user progress
* fixing progress overview layout
* fixing webvideodata in VideoBlock
* remove Metrics

4.0.5
-----
* fixing TestBlock layout issues
* fix HTMLBlock links and handle wysiwyg content
* fix migration 1 for studip 4.0
* fixing mark as html issue

4.0.4
-----
* fix post overview redirect
* set chdate on block save
* fix migration sql statement

4.0.3 RC
-----
* same features as 3.0.3

4.0.0
-----
* change from requirejs to webpack
* add new block: AssortBlock
* add new block: CodeBlock
* add new block: DownloadBlock
* add new block: GalleryBlock
* add new block: KeyPointBlock
* add new block: LinkBlock
* add new block: SearchBlock
* add new block: PdfBlock
* add new block: PostBlock (Courseware discussions)
* imporved iFrameBlock with CC informations
* modernized TestBlock compatible with vips 1.3
* ability to switch off horizontal navigation
* embed horizontal navigation in sidebar navigation (optional)
* comprehensive progress overview for teachers
* discussion overview for teachers
* overview of new contents in the courseware
* responsive layout to fit mobile devices
* courseware can be used in public courses

3.2.0
-----
* add new block: DialogCardsBlock
* add new block: EmbedBlock
* add new block: InteractiveVideoBlock
* add new block: BeforeAfterBlock
* add new block: ChartBlock
* add new block: OpenCastBlock
* new block adder design
* modernize models for Stud.IP >= 4.2
* cleanup VideoBlock

3.1.0
-----
* enhance TestBlock functions
* better usability for postoverview
* fixing wrong change date
* SearchBlock: fix encoding issue 
* fix cid in news controller
* fix progress calculation in cpo
* fix some old layout issues
* add functions for DatenschutzPlugin 

3.0.7
-----
* modernized look and feel
* enhance discussion overview and PostBlock - post can now be hidden
* improve DownloadBlock author view
* improve TestBlock - use vips.js, embed character picker, ready for vips 1.4
* remove Metrics
* some little layout and bug fixes

3.0.6
-----
* fixing user progress
* fixing progress overview layout
* fixing webvideodata in VideoBlock
* remove Metrics

3.0.5
-----
* fixing TestBlock layout issues
* fix HTMLBlock links and handle wysiwyg content
* fixing mark as html issue

3.0.4
-----
* fix post overview redirect
* set chdate on block save
* fix migration sql statement

3.0.3
-----
* fix PdfBlock

3.0.2
-----
* fix export and import encoding

3.0.1
-----
* fix TestBlock link and layout
* enhance sidebar layout

3.0.0
-----
* change from requirejs to webpack
* add new block: AssortBlock
* add new block: CodeBlock
* add new block: DownloadBlock
* add new block: GalleryBlock
* add new block: KeyPointBlock
* add new block: LinkBlock
* add new block: SearchBlock
* add new block: PdfBlock
* add new block: PostBlock (Courseware discussions)
* imporved iFrameBlock with CC informations
* modernized TestBlock compatible with vips 1.3
* ability to switch off horizontal navigation
* embed horizontal navigation in sidebar navigation (optional)
* comprehensive progress overview for teachers
* discussion overview for teachers
* overview of new contents in the courseware
* responsive layout to fit mobile devices
* courseware can be used in public courses
* new sidebar layout

2.1.0
-----
* add new block: AudioBlock
* add new block: DownloadBlock
* imporved iFrameBlock with user specific token
* prevent navigation on change for all blocks
* TestBlock: practise has to be submitted but not corrected to continue
* VideoBlock: fixing openCast URLs
* cleaning author views
* switch from png icons to svg icons
* adding icon to studip course overview (counting new blocks, sections and chapters)
* adding plugin preview image

2.0.2
-----
* remove obsolete i18n filename, fix LocalizationController
* enhance import API
* fix TestBlock issue
* fix subchapters' check mark icon
* fix color of subchapters' check mark
* fix CSS of progress page
* use getPluginURL instead
* fix tutor rights in settings
* transfer course activations from old Mooc.IP
* disable vips tab in courseware settings
* fix empty worksheet problem
* imporved VideoBlock 

2.0.1
-----
* Correct display of completed sections
* Keep plugin activations when migration from old Mooc.IP-plugin

2.0
---
* Splitted the plugin Mooc.IP into "Mooc - OpenCourses" and "Mooc - Courseware". This is the "Mooc - Courseware"-part
