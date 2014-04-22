# -*- coding: iso-8859-15 -*-
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
import unittest, time, re
import mysuite

class Test(unittest.TestCase):
    def setUp(self):
        self.driver = mysuite.getOrCreateWebdriver()
        self.driver.implicitly_wait(30)
        self.base_url = "http://vm036.rz.uos.de/studip/mooc/"
        self.verificationErrors = []
        self.accept_next_alert = True
    
    def test_(self):
        driver = self.driver
        driver.find_element_by_xpath("//section[@id='courseware']/div/button[2]").click()
        driver.find_element_by_xpath("//button[@data-type='TestBlock']").click()
        driver.find_element_by_xpath("//section/section/div/button").click()
        driver.find_element_by_xpath("//section/section/div/button[2]").click()
        self.assertRegexpMatches(self.close_alert_and_get_its_text(), r"^Wollen Sie wirklich löschen[\s\S]$")
        
    
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
            return alert_text.encode('utf-8')
        finally: self.accept_next_alert = True
    
    def tearDown(self):
        #self.driver.quit()
        time.sleep(1)
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":
    unittest.main()
