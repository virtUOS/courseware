# -*- coding: iso-8859-15 -*-
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import NoSuchElementException
import unittest, time, re
import mysuite

class HtmlBlock(unittest.TestCase):
    def setUp(self):
        self.driver = mysuite.getOrCreateWebdriver()
        self.driver.implicitly_wait(30)
        self.base_url = "http://vm036.rz.uos.de/studip/mooc/"
        self.verificationErrors = []
        self.accept_next_alert = True
    
    def test_html_block(self):
        driver = self.driver
        driver.find_element_by_xpath("//section[@id='courseware']/div/button[2]").click()
        driver.find_element_by_xpath("//button[@data-type='HtmlBlock']").click()
        for i in range(60):
            try:
                if self.is_element_present(By.CSS_SELECTOR, "section.HtmlBlock"): break
            except: pass
            time.sleep(1)
        else: self.fail("time out")
        driver.find_element_by_xpath("//section/section/div/button").click()
        for i in range(60):
            try:
                if self.is_element_present(By.NAME, "content"): break
            except: pass
            time.sleep(1)
        else: self.fail("time out")
        driver.find_element_by_name("content").clear()
        driver.find_element_by_name("content").send_keys("Selenium Test")
        driver.find_element_by_name("save").click()
        for i in range(60):
            try:
                if self.is_element_present(By.CSS_SELECTOR, "div.content"): break
            except: pass
            time.sleep(1)
        else: self.fail("time out")
        try: self.assertEqual("Selenium Test", driver.find_element_by_css_selector("div.content").text)
        except AssertionError as e: self.verificationErrors.append(str(e))
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
            return alert_text
        finally: self.accept_next_alert = True
    
    def tearDown(self):
        #self.driver.quit()
        time.sleep(1)
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":
    unittest.main()
