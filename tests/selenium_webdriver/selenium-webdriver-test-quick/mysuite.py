# -*- coding: iso-8859-15 -*-
import unittest
import time
from selenium import webdriver

DRIVER = None

def getOrCreateWebdriver():
	global DRIVER
	DRIVER = DRIVER or webdriver.Firefox()
	return DRIVER

def suite():
    test_suite = unittest.TestSuite()
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('login'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('htmlblock'))
    #test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('testblock'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('videoblock'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('iframeblock'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('blubberblock'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('logout'))
    return test_suite

def suite2():
    test_suite = unittest.TestSuite()
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('login'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('htmlblock'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('logout'))
    return test_suite

if __name__ == "__main__":
    TEST_RUNNER = unittest.TextTestRunner()
    TEST_SUITE = suite()
    TEST_RUNNER.run(TEST_SUITE)
	

