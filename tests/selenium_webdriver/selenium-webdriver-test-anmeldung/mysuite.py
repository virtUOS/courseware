 # -*- coding: iso-8859-15 -*-
import unittest


def suite():
    test_suite = unittest.TestSuite()
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('ohne-email'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('ohne-vname'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('ohne-nname'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('nicht-einverst'))
    test_suite.addTest(unittest.defaultTestLoader.loadTestsFromName('einverst'))
    return test_suite


if __name__ == "__main__":
    TEST_RUNNER = unittest.TextTestRunner()
    TEST_SUITE = suite()
    TEST_RUNNER.run(TEST_SUITE)
	

