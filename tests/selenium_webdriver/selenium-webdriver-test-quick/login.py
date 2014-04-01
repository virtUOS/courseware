# -*- coding: iso-8859-15 -*-
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
import unittest, time, re
import mysuite
from socket import error as socket_error
class Login(unittest.TestCase):
    def setUp(self):
        self.driver = mysuite.getOrCreateWebdriver()
        self.driver.implicitly_wait(30)
        self.base_url = "http://vm036.rz.uos.de/studip/mooc/"
        self.verificationErrors = []
        self.accept_next_alert = True
    
    def test_login(self):
        driver = self.driver
        driver.get("http://vm036.rz.uos.de/studip/mooc/index.php?again=yes&cancel_login=1")
        driver.find_element_by_id("loginname").clear()
        driver.find_element_by_id("loginname").send_keys("test_dozent")
        driver.find_element_by_id("password").clear()
        driver.find_element_by_id("password").send_keys("testing")
        driver.find_element_by_name("Login").click()
        driver.find_element_by_css_selector("#nav_mooc > a > span").click()
        driver.find_element_by_css_selector("span[title=\"Alle Kurse\"]").click()
        driver.find_element_by_link_text("Zum Kurs").click()
        driver.find_element_by_css_selector("span[title=\"Courseware\"]").click()
        driver.find_element_by_link_text("Kapitel 2").click()
    
    def is_element_present(self, how, what):
        try: self.driver.find_element(by=how, value=what)
        except NoSuchElementException, e: return False
        return True
    
    def is_alert_present(self):
        try: self.driver.switch_to_alert()
        except NoAlertPresentException, e: return False
        return True
    
    def close_alert_and_get_its_text(self):
        try:
            alert = self.driver.switch_to_alert()
            alert_text = alert.text
            if self.accept_next_alert:
                alert.accept()
            else:
                alert.dismiss()
            return alert_text
        finally: self.accept_next_alert = True
    
    def tearDown(self):
	#self.driver.quit()
        self.assertEqual([], self.verificationErrors)
	

if __name__ == "__main__":
    unittest.main()
