 # -*- coding: iso-8859-15 -*-
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
import unittest, time, re, random

class Einverstanden(unittest.TestCase):
    def setUp(self):
        self.driver = webdriver.Firefox()
        self.driver.implicitly_wait(30)
        self.base_url = "http://vm036.rz.uos.de/"
        self.verificationErrors = []
        self.accept_next_alert = True
    
    def test_einverstanden(self):
        driver = self.driver
        driver.get(self.base_url + "/studip/mooc/index.php")
        driver.find_element_by_css_selector("#nav_mooc > a > span").click()
        driver.find_element_by_css_selector("span[title=\"Alle Kurse\"]").click()
        driver.find_element_by_xpath("//a[@href=\"/studip/mooc/plugins.php/mooc/courses/show/2358add583efc4c04d209ff257b9d9c4?moocid=2358add583efc4c04d209ff257b9d9c4\"]").click()
        driver.find_element_by_link_text("Zur Anmeldung").click()
        driver.find_element_by_name("vorname").clear()
        driver.find_element_by_name("vorname").send_keys("Max")
        driver.find_element_by_name("nachname").clear()
        driver.find_element_by_name("nachname").send_keys("Mustermann")
	driver.find_element_by_name("mail").clear()
        driver.find_element_by_name("mail").send_keys("max"+ str(random.randint(0, 11111))+"@uos.de")
        driver.find_element_by_name("accept_tos").click()
        driver.find_element_by_css_selector("button[name=\"Jetzt anmelden\"]").click()
        try: self.assertEqual(u"Vielen Danke für Ihre Anmeldung!", driver.find_element_by_css_selector("h2").text)
        except AssertionError as e: self.verificationErrors.append(str(e))
    
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
        self.driver.quit()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":
    unittest.main()
