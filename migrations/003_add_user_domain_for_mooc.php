<?php

class AddUserDomainForMooc extends Migration {

    public function __construct($verbose = FALSE)
    {
        require_once __DIR__ . '/../models/mooc/constants.php';

        parent::__construct($verbose);
    }


    public function description () {
        return 'add userdomain for (foreign) users participating in a mooc-course';
    }


    public function up () {
        $db = DBManager::get();
        $stmt = $db->prepare("INSERT INTO userdomains"
                . "(userdomain_id, name)"
                . "VALUES (:id, :id)");
        $stmt->bindValue(':id', \Mooc\USER_DOMAIN_NAME);
        $stmt->execute();
    }


    public function down () {
        $db = DBManager::get();
        $stmt = $db->prepare("DELETE FROM userdomains"
                . "WHERE userdomain_id = _id");
        $stmt->bindValue(':id', \Mooc\USER_DOMAIN_NAME);
        $stmt->execute();
    }
}
