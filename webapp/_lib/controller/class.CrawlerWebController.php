<?php
/**
 *
 * ThinkUp/webapp/_lib/controller/class.CrawlerWebController.php
 *
 * Copyright (c) 2009-2011 Gina Trapani, Guillaume Boudreau
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * Crawler Web Controller
 *
 * Runs crawler from the web for the logged-in user and outputs logging into a text area.
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2011 Gina Trapani, Guillaume Boudreau
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 */
class CrawlerWebController extends ThinkUpAuthAPIController {

    public function authControl() {
        Utils::defineConstants();

        if ($this->isAPICall()) {
            // If the request comes from an API call, output JSON instead of HTML
            $this->setContentType('application/json; charset=UTF-8');
        } else {
            $this->setContentType('text/html; charset=UTF-8');
            $this->setViewTemplate('crawler.run-top.tpl');
            echo $this->generateView();
            $config = Config::getInstance();
            $config->setValue('log_location', false); //this forces output to just echo to page
            $logger = Logger::getInstance();
            $logger->close();
        }

        try {
            $logger = Logger::getInstance();
            if (isset($_GET['log']) && $_GET['log'] == 'full') {
                $logger->setVerbosity(Logger::ALL_MSGS);
                echo '<pre style="font-family:Courier;font-size:10px;">';
            } else {
                $logger->setVerbosity(Logger::USER_MSGS);
                $logger->enableHTMLOutput();
            }
            $crawler = Crawler::getInstance();
            //close session so that it's not locked by long crawl
            session_write_close();
            $crawler->crawl();
            $logger->close();
        } catch (CrawlerLockedException $e) {
            if ($this->isAPICall()) {
                // Will be caught and handled in ThinkUpController::go()
                throw $e;
            } else {
                // Will appear in the textarea of the HTML page
                echo $e->getMessage();
            }
        }

        if ($this->isAPICall()) {
            echo json_encode((object) array('result' => 'success'));
        } else {
            $this->setViewTemplate('crawler.run-bottom.tpl');
            echo $this->generateView();
        }
    }
}