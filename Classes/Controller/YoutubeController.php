<?php
namespace Socialstream\SocialStream\Controller;


    /***************************************************************
     *
     *  Copyright notice
     *
     *  (c) 2016
     *
     *  All rights reserved
     *
     *  This script is part of the TYPO3 project. The TYPO3 project is
     *  free software; you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation; either version 3 of the License, or
     *  (at your option) any later version.
     *
     *  The GNU General Public License can be found at
     *  http://www.gnu.org/copyleft/gpl.html.
     *
     *  This script is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  This copyright notice MUST APPEAR in all copies of the script!
     ***************************************************************/

/**
 * YoutubeController
 */
class YoutubeController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * pageRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\PageRepository
     * @inject
     */
    protected $pageRepository = NULL;


    /**
     * videoRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\VideoRepository
     * @inject
     */
    protected $videoRepository = NULL;


    protected $ytappid = "";
    protected $ytappsecret = "";
    protected $ytappredirect = "";
    protected $limitPosts = "";
    protected $limitGalleries = "";
    protected $maxText = 0;
    protected $maxWidth = 0;
    protected $maxHeight = 0;
    protected $storagePid = 0;
    protected $tmp = "/tmp/";
    protected $clearStrings = array('\ud83c\u', '\ud83d\u', '\u2600\u');

    public $rootPage = 1;

    protected $streamtype = 3;

    public function initializeAction()
    {
        $this->ytappid = $this->settings['ytappid'];
        $this->ytappsecret = $this->settings['ytappsecret'];
        $this->ytappredirect = $this->settings['ytappredirect'];
        $this->limitPosts = $this->settings['limitPosts'];
        $this->limitGalleries = $this->settings['limitGalleries'];
        $this->maxText = $this->settings['maxText'];
        $this->maxWidth = $this->settings['maxWidth'];
        $this->maxHeight = $this->settings['maxHeight'];
        $this->storagePid = $this->settings['storagePid'];
        $this->tmp = $this->settings['tmp'];
        if (substr($this->tmp, -1) != "/") $this->tmp . "/";
    }

    /**
     * action token
     *
     * @return void
     */
    public function tokenAction()
    {
        $vars = $this->request->getArguments();
        $pagename = $vars["page"]["name"];
        if ($pagename) {
            if ($pagename != "me") {
                $dbpage = $this->pageRepository->searchByName($pagename, $this->streamtype);
                if ($dbpage->toArray()) {
                    $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.already', 'social_stream');
                    $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
                    $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg));
                } else {
                    $headers = get_headers("https://www.youtube.com/user/" . $pagename);
                    $resp = substr($headers[0], 9, 3);
                    if ($resp != 200 && $resp != 302 && $resp != 999) {
                        $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nopage', 'social_stream');
                        $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
                        $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg));
                    }
                }
            }
        }

        $accesstoken = $_GET["code"];
        if ($accesstoken) {
            $url = "https://accounts.google.com/o/oauth2/token";
            $curl = curl_init();
            $parameters = array(
                'code' => $accesstoken,
                'client_id' => $this->ytappid,
                'client_secret' => $this->ytappsecret,
                'grant_type' => "authorization_code",
                'redirect_uri' => filter_var(trim($this->ytappredirect, "/") . '/typo3conf/ext/social_stream/Classes/Utility/StoreToken.php', FILTER_SANITIZE_URL)
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
            $response = curl_exec($curl);
            curl_close($curl);
            $infos = json_decode($response);
            $tk = $infos->access_token;
            $exp = $infos->expires_in;
            $page = new \Socialstream\SocialStream\Domain\Model\Page();
            $page->setName($_GET["name"]);
            $page->setStreamtype(1);
            $page->setToken($tk);
            if (property_exists($infos, "refresh_token"))
                $page->setTokensecret($infos->refresh_token);
            $page->setExpires(time() + $exp);
            $this->forward('create', null, null, array('page' => $page, 'short' => 1, 'close' => 1));
        } else {
            if (!$_GET["name"]) {
                $this->uriBuilder->reset();
                $this->uriBuilder->setArguments(array(
                    'tx_socialstream_web_socialstreambe1' => array(
                        'action' => 'token',
                        'controller' => 'Youtube'
                    ),
                    'name' => $pagename
                ));
                $this->uriBuilder->setCreateAbsoluteUri(1);
                $url = urlencode($this->uriBuilder->buildBackendUri());

                $redirect = urlencode(filter_var(trim($this->ytappredirect, "/") . '/typo3conf/ext/social_stream/Classes/Utility/StoreToken.php', FILTER_SANITIZE_URL));
                $accessurl = "https://accounts.google.com/o/oauth2/auth?client_id=" . $this->ytappid . "&scope=https://www.googleapis.com/auth/youtube.readonly&response_type=code&access_type=offline&state=$url&redirect_uri=$redirect";
                $this->view->assign('accessurl', $accessurl);
                $this->view->assign('name', $pagename);
            } else {
                $this->view->assign('name', $_GET["name"]);
            }
        }
    }

    /**
     * action create
     *
     * @param \Socialstream\SocialStream\Domain\Model\Page $page
     * @param int $short
     * @param int $close
     * @return void
     */
    public function createAction(\Socialstream\SocialStream\Domain\Model\Page $page, $short = 0, $close = 0)
    {
        if ($page->getName()) {
            $already = 0;
            if ($page->getName() != "me") {
                $dbpage = $this->pageRepository->searchByName($page->getName(), $this->streamtype);
                if ($dbpage->toArray()) {
                    $already = 1;
                    $page = $dbpage[0];
                }
            }

            $storageRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $clear = 0;
            $storage = $storageRepository->findByUid('1');
            if ($storage->hasFolder("youtube")) {
                $targetFolder = $storage->getFolder('youtube');
            } else {
                $targetFolder = $storage->createFolder('youtube');
            }

            try {
                // ### get Page Data ###
                $page = $this->pageProcess($page, $storage, $targetFolder, $already);
                if ($targetFolder->hasFolder($page->getId())) {
                    $subFolder = $targetFolder->getSubfolder($page->getId());
                } else {
                    $subFolder = $targetFolder->createFolder($page->getId());
                }

                // ### get Video Page ###
                if ($subFolder->hasFolder("video")) {
                    $videoFolder = $subFolder->getSubfolder("video");
                } else {
                    $videoFolder = $subFolder->createFolder("video");
                }
                $clear += $this->videoProcess($page, $storage, $targetFolder, $subFolder, $videoFolder, $short);

                if ($clear > 0) {
                    $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
                    $tce->clear_cacheCmd('cacheTag:socialStream');
                }

                if ($close) {
                    $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.created', 'social_stream');
                } else {
                    $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.imported', 'social_stream');
                }
                $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.success', 'social_stream');
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => $close));

            } catch (\TYPO3\CMS\Core\Error\Exception $e) {
                $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nodata', 'social_stream');
                $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
                //$this->redirect('message', null, null, array('head' => $head, 'message' => $e->getMessage()));
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => $close));
            }

        } else {
            $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.noname', 'social_stream');
            $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
            $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => $close));
        }
    }

    /**
     * action getYoutube
     *
     * @return void
     */
    public function getYoutubeAction()
    {
        $this->initTSFE($this->rootPage, 0);
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $this->settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Socialstream');
        $this->pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Socialstream\\SocialStream\\Domain\\Repository\\PageRepository');
        $this->videoRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Socialstream\\SocialStream\\Domain\\Repository\\VideoRepository');
        $this->initializeAction();
        $short = 0;
        $pages = $this->pageRepository->findAll();
        $clear = 0;

        foreach ($pages as $page) {
            $storageRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $storage = $storageRepository->findByUid('1');
            if ($storage->hasFolder("youtube")) {
                $targetFolder = $storage->getFolder('youtube');
            } else {
                $targetFolder = $storage->createFolder('youtube');
            }

            try {
                // ### get Page Data ###
                $page = $this->pageProcess($page, $storage, $targetFolder, 1, 0);
                if ($targetFolder->hasFolder($page->getId())) {
                    $subFolder = $targetFolder->getSubfolder($page->getId());
                } else {
                    $subFolder = $targetFolder->createFolder($page->getId());
                }

                // ### get Video Page ###
                if ($subFolder->hasFolder("video")) {
                    $videoFolder = $subFolder->getSubfolder("video");
                } else {
                    $videoFolder = $subFolder->createFolder("video");
                }
                $clear += $this->videoProcess($page, $storage, $targetFolder, $subFolder, $videoFolder, $short);


            } catch (\TYPO3\CMS\Core\Error\Exception $e) {
                echo "" . $e->getMessage();
            }
        }

        if ($clear > 0) {
            $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
            $tce->clear_cacheCmd('cacheTag:socialStream');
        }

    }

    private function pageProcess($page, $storage, $targetFolder, $already, $showerror = 1)
    {
        $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');

        if (!$this->ytappid || !$this->ytappsecret) {
            $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.noapp', 'social_stream');
            $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
            if ($showerror) {
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => 1));
            } else {
                return $page;
            }
        }
        if (!$this->storagePid) {
            $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nostorage', 'social_stream');
            $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
            if ($showerror) {
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => 1));
            } else {
                return $page;
            }
        }

        $tk = $page->getToken();
        $expdiff = ($page->getExpires() - time());

        if ($expdiff < 0 && $tk) {
            $url = "https://accounts.google.com/o/oauth2/token";
            $curl = curl_init();
            $parameters = array(
                'client_id' => $this->ytappid,
                'client_secret' => $this->ytappsecret,
                'refresh_token' => $page->getTokensecret(),
                'grant_type' => "refresh_token",
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
            $response = curl_exec($curl);
            curl_close($curl);

            $infos = json_decode($response);
            $tk = $infos->access_token;
            $exp = $infos->expires_in;
            $page->setToken($tk);
            $page->setExpires(time() + $exp);
        }

        try {
            if ($page->getName()) {
                $elem = (file_get_contents("https://www.googleapis.com/youtube/v3/channels?part=snippet&forUsername=" . $page->getName() . "&access_token=$tk"));
            }
        } catch (\TYPO3\CMS\Core\Error\Exception $e) {
            $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nodata', 'social_stream');
            $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
            if ($showerror) {
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => 1));
            } else {
                return $page;
            }
        }


        if (!$elem) {
            $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nodata', 'social_stream');
            $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
            if ($showerror) {
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg, 'close' => 1));
            } else {
                return $page;
            }
        }
        foreach ($this->clearStrings as $str) {
            while (strpos($elem, $str) !== false) {
                $pos = strpos($elem, $str);
                $elem = substr_replace($elem, '', $pos, 12);
            }
        }

        $elem = json_decode($elem);

        $helppage = $this->pageRepository->searchById($elem->items[0]->id, $this->streamtype);
        if ($helppage) {
            $helppage->setToken($page->getToken());
            $helppage->setExpires($page->getExpires());
            $page = $helppage;
            $already = 1;
        }

        $page->setId($elem->items[0]->id);
        $page->setAbout($elem->items[0]->snippet->title);
        if ($elem->items[0]->snippet->description) $page->setAbout($elem->items[0]->snippet->description);
        $page->setLink("https://youtube.com/user/".$page->getName());
        $page->setCoverUrl("https://youtube.com/user/".$page->getName());
        $page->setStreamtype($this->streamtype);
        $bildname = "photo.jpg";
        $bildurl = $elem->items[0]->snippet->thumbnails->high->url;
        $page->setPictureUrl($elem->items[0]->snippet->thumbnails->high->url);

        if ($already == 1) {
            $this->pageRepository->update($page);
        } else {
            $this->pageRepository->add($page);
        }
        $persistenceManager->persistAll();

        if ($targetFolder->hasFolder($page->getId())) {
            $subFolder = $targetFolder->getSubfolder($page->getId());
        } else {
            $subFolder = $targetFolder->createFolder($page->getId());
        }

        if (!$subFolder->hasFile($bildname)) {
            if ($bildurl) {
                copy($bildurl, $this->tmp . $bildname);
                $movedNewFile = $storage->addFile($this->tmp . $bildname, $subFolder, $bildname);
                $bild = $movedNewFile->getUid();
            }
            if ($page->getPicture()) {
                $GLOBALS["TYPO3_DB"]->exec_UPDATEquery("sys_file_reference", "uid=" . $page->getPicture()->getUid(), array('deleted' => '1'));
            }
        } elseif (!$page->getPicture()) {
            $bild = $storage->getFile("/" . $targetFolder->getName() . "/" . $subFolder->getName() . "/" . $bildname);
            $bild = $bild->getUid();
        }

        if ($bild) {
            $data = array();
            $data['sys_file_reference']['NEW123'] = array(
                'uid_local' => $bild,
                'uid_foreign' => $page->getUid(), // uid of your content record
                'tablenames' => '	tx_socialstream_domain_model_page',
                'fieldname' => 'picture',
                'pid' => $this->storagePid, // parent id of the parent page
                'table_local' => 'sys_file',
            );
            $data['tx_socialstream_domain_model_page'][$page->getUid()] = array('picture' => 'NEW123'); // set to the number of images?

            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
            $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
            $tce->bypassAccessCheckForRecords = TRUE;
            $tce->start($data, array());
            $tce->admin = TRUE;
            $tce->process_datamap();
        }

        return $page;
    }

    private function videoProcess($page, $storage, $targetFolder, $subFolder, $videoFolder, $short, $paging = "")
    {
        $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
        $channelId = $page->getId();
        $tk = $page->getToken();
        if ($paging) {
            if ($short) {
                $stream = (file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=$channelId&access_token=$tk&maxResults=$this->limitGalleries&pageToken=$paging&type=video"));
            } else {
                $stream = (file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=$channelId&access_token=$tk&maxResults=50&pageToken=$paging&type=video"));
            }
        } else {
            if ($short) {
                $stream = (file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=$channelId&access_token=$tk&maxResults=$this->limitGalleries&type=video"));
            } else {
                $stream = (file_get_contents("https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=$channelId&access_token=$tk&maxResults=50&type=video"));
            }
        }
        $stream = json_decode($stream);
        foreach ($stream->items as $entry) {
            $video = $this->videoRepository->findHiddenById($entry->id->videoId, $page->getUid());
            if ($video->toArray()) {
                $videoalready = 1;
                $video = $video[0];
            } else {
                $video = new \Socialstream\SocialStream\Domain\Model\Video();
                $videoalready = 0;
            }
            $video->setId($entry->id->videoId);
            $video->setPictureUrl($entry->snippet->thumbnails->high->url);
            $video->setCreatedTime((new \DateTime($entry->snippet->publishedAt)));
            $video->setTitle($entry->snippet->title);
            $video->setDescription($entry->snippet->description);
            $video->setPage($page);

            if ($videoalready) {
                $this->videoRepository->update($video);
            } else {
                $this->videoRepository->add($video);
                $clear = 1;
            }
            $persistenceManager->persistAll();

            $bild = NULL;
            $bildurl = $video->getPictureUrl();
            $bildname = $entry->id->videoId . ".jpg";

            if (!$videoFolder->hasFile($bildname) && $bildname) {
                if ($this->exists($bildurl)) {
                    $this->grab_image($bildurl, $this->tmp . $bildname);
                    $movedNewFile = $storage->addFile($this->tmp . $bildname, $videoFolder, $bildname);
                    $bild = $movedNewFile->getUid();
                }
                if ($video->getPicture()) {
                    $GLOBALS["TYPO3_DB"]->exec_UPDATEquery("sys_file_reference", "uid=" . $video->getPicture()->getUid(), array('deleted' => '1'));
                }
            } elseif (!$video->getPicture() && $bildname) {
                $bild = $storage->getFile("/" . $targetFolder->getName() . "/" . $subFolder->getName() . "/" . $videoFolder->getName() . "/" . $bildname);
                $bild = $bild->getUid();
            }
            if ($bild) {
                $data = array();
                $data['sys_file_reference']['NEW123456'] = array(
                    'uid_local' => $bild,
                    'uid_foreign' => $video->getUid(), // uid of your content record
                    'tablenames' => '	tx_socialstream_domain_model_video',
                    'fieldname' => 'picture',
                    'pid' => $this->storagePid, // parent id of the parent page
                    'table_local' => 'sys_file',
                );
                $data['tx_socialstream_domain_model_video'][$video->getUid()] = array('picture' => 'NEW123456'); // set to the number of images?

                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
                $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
                $tce->bypassAccessCheckForRecords = TRUE;
                $tce->start($data, array());
                $tce->admin = TRUE;
                $tce->process_datamap();
                $clear = 1;
            }

        }
        if ($stream->nextPageToken && !$short) {
            $clear += $this->videoProcess($page, $storage, $targetFolder, $subFolder, $videoFolder, $short, $stream->nextPageToken);
        }
        return $clear;
    }


    protected function grab_image($url, $saveto)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (file_exists($saveto)) {
            unlink($saveto);
        }
        $fp = fopen($saveto, 'x');
        fwrite($fp, $raw);
        fclose($fp);
    }

    protected function initTSFE($id = 1, $typeNum = 0)
    {
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
            $GLOBALS['TT']->start();
        }
        $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'], $id, $typeNum);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();

    }

    protected function exists($path)
    {
        return (@fopen($path, "r") == true);
    }

    protected function header_req($url)
    {
        $channel = curl_init();
        curl_setopt($channel, CURLOPT_URL, $url);
        curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($channel, CURLOPT_TIMEOUT, 10);
        curl_setopt($channel, CURLOPT_HEADER, true);
        curl_setopt($channel, CURLOPT_NOBODY, true);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201');
        curl_setopt($channel, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($channel, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($channel, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_exec($channel);
        $httpCode = curl_getinfo($channel, CURLINFO_HTTP_CODE);
        curl_close($channel);
        return $httpCode;
    }

}