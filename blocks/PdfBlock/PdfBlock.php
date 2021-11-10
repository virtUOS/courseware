<?php
namespace Mooc\UI\PdfBlock;

use Mooc\UI\Block;

class PdfBlock extends Block
{
    const NAME = "PDF mit Vorschau";
    const BLOCK_CLASS = "multimedia";
    const DESCRIPTION = "Eine PDF-Datei aus dem Dateibereich anzeigen";

    public function initialize()
    {
        $this->defineField("pdf_file", \Mooc\SCOPE_BLOCK, "");
        $this->defineField("pdf_filename", \Mooc\SCOPE_BLOCK, "");
        $this->defineField("pdf_file_id", \Mooc\SCOPE_BLOCK, "");
        $this->defineField("pdf_title", \Mooc\SCOPE_BLOCK, "");
        $this->defineField("pdf_disable_download", \Mooc\SCOPE_BLOCK, false);
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return ["inactive" => true];
        }
        $file_ref = \FileRef::find($this->pdf_file_id);
        if ($file_ref) {
            $access = $this->isFileDownloadable($file_ref);
        } else {
            $access = true;
        }
        $this->setGrade(1.0);
        $plugin_manager = \PluginManager::getInstance();
        $courseware_path = $plugin_manager
            ->getPlugin("Courseware")
            ->getPluginURL();

        return array_merge($this->getAttrArray(), [
            "access" => $access,
            "courseware_path" => $courseware_path,
        ]);
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $file_id = $this->pdf_file_id;
        $files_arr = $this->showFiles($file_id);

        $no_files =
            empty($files_arr["userfilesarray"]) &&
            empty($files_arr["coursefilesarray"]) &&
            $files_arr["pdf_id_found"] == false &&
            empty($file_id);

        if (!$files_arr["pdf_id_found"] && !empty($file_id)) {
            $other_user_file = [
                "id" => $file_id,
                "name" => $this->pdf_filename,
                "pdf_file" => $this->pdf_file,
            ];
        } else {
            $other_user_file = false;
        }

        return array_merge($this->getAttrArray(), [
            "user_pdf_files" => $files_arr["userfilesarray"],
            "course_pdf_files" => $files_arr["coursefilesarray"],
            "no_pdf_files" => $no_files,
            "other_user_file" => $other_user_file,
        ]);
    }

    public function preview_view()
    {
        return ["pdf_filename" => $this->pdf_filename];
    }

    private function getAttrArray()
    {
        return [
            "pdf_file" => $this->pdf_file,
            "pdf_filename" => $this->pdf_filename,
            "pdf_file_id" => $this->pdf_file_id,
            "pdf_title" => $this->pdf_title,
            "pdf_disable_download" => $this->pdf_disable_download,
        ];
    }

    private function showFiles($file_id)
    {
        $coursefilesarray = [];
        $userfilesarray = [];
        $course_folders = \Folder::findBySQL(
            "range_id = ? AND folder_type NOT IN (?)",
            [$this->container["cid"], ["HiddenFolder", "HomeworkFolder"]]
        );
        $hidden_folders = $this->getHiddenFolders();
        $course_folders = array_merge($course_folders, $hidden_folders);
        $user_folders = \Folder::findBySQL(
            "range_id = ? AND folder_type = ? ",
            [$this->container["current_user_id"], "PublicFolder"]
        );
        $pdf_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL("folder_id = ?", [$folder->id]);
            foreach ($file_refs as $ref) {
                if ($ref->mime_type == "application/pdf" && !$ref->isLink()) {
                    $coursefilesarray[] = $ref;
                }
                if ($ref->id == $file_id) {
                    $pdf_id_found = true;
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL("folder_id = ?", [$folder->id]);
            foreach ($file_refs as $ref) {
                if ($ref->mime_type == "application/pdf" && !$ref->isLink()) {
                    $userfilesarray[] = $ref;
                }
                if ($ref->id == $file_id) {
                    $pdf_id_found = true;
                }
            }
        }

        return [
            "coursefilesarray" => $coursefilesarray,
            "userfilesarray" => $userfilesarray,
            "pdf_id_found" => $pdf_id_found,
        ];
    }

    private function getHiddenFolders()
    {
        $folders = [];

        $hidden_folders = \Folder::findBySQL(
            "range_id = ? AND folder_type = ?",
            [$this->container["cid"], "HiddenFolder"]
        );

        foreach ($hidden_folders as $hidden_folder) {
            if ($hidden_folder->data_content["download_allowed"] == 1) {
                array_push($folders, $hidden_folder);
            }
        }

        return $folders;
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset($data["pdf_filename"])) {
            $this->pdf_filename = $data["pdf_filename"];
        }
        if (isset($data["pdf_disable_download"])) {
            $this->pdf_disable_download = $data["pdf_disable_download"];
        }
        if (isset($data["pdf_file_id"]) && $data["pdf_file_id"] != "") {
            $this->pdf_file_id = $data["pdf_file_id"];
            $file_ref = new \FileRef($this->pdf_file_id);
            $this->pdf_file = $this->getFileURL($file_ref);
        }
        if (isset($data["pdf_title"])) {
            $this->pdf_title = \STUDIP\Markup::purifyHtml($data["pdf_title"]);
        }

        return;
    }

    public function exportProperties()
    {
        return $this->getAttrArray();
    }

    public function getFiles()
    {
        $files = [];
        if ($this->pdf_file_id == "") {
            return $files;
        }
        $file_ref = new \FileRef($this->pdf_file_id);
        $file = new \File($file_ref->file_id);
        $files[] = [
            "id" => $this->pdf_file_id,
            "name" => $file_ref->name,
            "description" => $file_ref->description,
            "filename" => $file->name,
            "filesize" => $file->size,
            "url" => $this->isFileAnURL($file_ref),
            "path" => $file->getPath(),
        ];

        return $files;
    }

    public function getHtmlExportData()
    {
        $array = $this->getAttrArray();
        $array["pdf_file"] =
            "./" . $array["pdf_file_id"] . "/" . $array["pdf_filename"];

        return $array;
    }

    public function getXmlNamespace()
    {
        return "http://moocip.de/schema/block/pdf/";
    }

    public function getXmlSchemaLocation()
    {
        return "http://moocip.de/schema/block/pdf/pdf-1.0.xsd";
    }

    public function importProperties(array $properties)
    {
        if (isset($properties["pdf_title"])) {
            $this->pdf_title = $properties["pdf_title"];
        }
        if (isset($properties["pdf_filename"])) {
            $this->pdf_filename = $properties["pdf_filename"];
        }
        if (isset($properties["pdf_disable_download"])) {
            $this->pdf_disable_download = $properties["pdf_disable_download"];
        } else {
            $this->pdf_disable_download = false;
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        foreach ($files as $file) {
            if ($file->name == "") {
                continue;
            }
            if ($this->pdf_filename == $file->name) {
                $this->pdf_file_id = $file->id;
                $this->pdf_file = $this->getFileURL($file);

                $this->save();
                return [$file->id];
            }
        }
    }
}
