# -*- coding: iso-8859-15 -*-
import unittest
import time
import sys
from selenium import webdriver

DRIVER = None

def getOrCreateWebdriver():
	global DRIVER
	DRIVER = DRIVER or webdriver.Firefox()
	return DRIVER

def suite():
    test_suite = unittest.TestSuite()
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('login'))
    #test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('wysiwyg'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('html'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('php'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('js'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('logout'))
    return test_suite

def suite2(testname):
    test_suite = unittest.TestSuite()
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('login'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName(testname))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('logout'))
    return test_suite

if __name__ == "__main__":
    if len(sys.argv) == 2:
		print '\033[1;32mJust using '+sys.argv[1]+' test.\033[1;m'
		print '\033[1;32mStarting testsuite now ...\033[1;m'
		TEST_RUNNER = unittest.TextTestRunner()
		TEST_SUITE = suite2(sys.argv[1])
		TEST_RUNNER.run(TEST_SUITE)
	else:
		print '\033[1;41m    You may also call this testsuit with a specific test.    \033[1;m'
		print '\033[1;41m    Just add the testname as an argument.                    \033[1;m'
		print '\033[1;32mStarting testsuite now ...\033[1;m'
		TEST_RUNNER = unittest.TextTestRunner()
		TEST_SUITE = suite()
		TEST_RUNNER.run(TEST_SUITE)

