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
 * FlickrController
 */
class FlickrController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * pageRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\PageRepository
     * @inject
     */
    protected $pageRepository = NULL;

    /**
     * postRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\PostRepository
     * @inject
     */
    protected $postRepository = NULL;

    /**
     * galleryRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\GalleryRepository
     * @inject
     */
    protected $galleryRepository = NULL;

    /**
     * eventRepository
     *
     * @var \Socialstream\SocialStream\Domain\Repository\EventRepository
     * @inject
     */
    protected $eventRepository = NULL;

    protected $flickrappid = "";
    protected $flickrappsecret = "";
    protected $limitPosts = "";
    protected $limitGalleries = "";
    protected $maxText = 0;
    protected $maxWidth = 0;
    protected $maxHeight = 0;
    protected $storagePid = 0;
    protected $tmp = "/tmp/";

    /**
     * @var string HTTP Method to use for API calls
     */
    private $method = 'GET';
    const OAUTH_REQUEST_TOKEN_SECRET = 'oauth_request_token_secret';
    const OAUTH_ACCESS_TOKEN_SECRET = 'oauth_access_token_secret';
    const API_ENDPOINT = 'https://api.flickr.com/services/rest';

    public $rootPage = 1;

    protected $streamtype = 7;

    public function initializeAction()
    {
        $this->flickrappid = $this->settings['flickrappid'];
        $this->flickrappsecret = $this->settings['flickrappsecret'];
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
            $dbpage = $this->pageRepository->searchByName($pagename, $this->streamtype);
            if ($dbpage->toArray()) {
                $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.already', 'social_stream');
                $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
                $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg));
            } else {
                $headers = get_headers("https://www.flickr.com/photos/" . $pagename);
                $resp = substr($headers[0], 9, 3);
                if ($resp != 200 && $resp != 302 && $resp != 999) {
                    $msg = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.nopage', 'social_stream');
                    $head = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('msg.error', 'social_stream');
                    $this->redirect('message', 'Page', null, array('head' => $head, 'message' => $msg));
                }
            }
        }

        $accesstoken = $_GET["oauth_token"];
        if ($accesstoken) {
            srand(time());
            $nonce = rand();
            $time = time();
            $parameter = array(
                "oauth_nonce" => $nonce,
                "oauth_timestamp" => $time,
                "oauth_consumer_key" => $this->flickrappid,
                "oauth_signature_method" => "HMAC-SHA1",
                "oauth_version" => "1.0",
                "oauth_verifier" => $_GET["oauth_verifier"],
                "oauth_token" => $_GET["oauth_token"],
            );
            $requestUrl = "https://www.flickr.com/services/oauth/access_token";
            $this->sign($requestUrl, $parameter);
            $data = $this->httpRequest($requestUrl, $parameter);
            parse_str($data, $tokenArray);
            $tk = $tokenArray['oauth_token'];
            $page = new \Socialstream\SocialStream\Domain\Model\Page();
            $page->setId($tokenArray["user_nsid"]);
            $page->setName($tokenArray["fullname"]);
            $page->setStreamtype(7);
            $page->setToken($tk);
            $page->setTokensecret($tokenArray['oauth_token_secret']);
            $this->forward('create', null, null, array('page' => $page, 'short' => 1, 'close' => 1));

        } else {
            if (!$_GET["name"]) {
                $this->uriBuilder->reset();
                $this->uriBuilder->setArguments(array(
                    'tx_socialstream_web_socialstreambe1' => array(
                        'action' => 'token',
                        'controller' => 'Flickr'
                    ),
                    'name' => $pagename
                ));
                $this->uriBuilder->setCreateAbsoluteUri(1);
                $url = $this->uriBuilder->buildBackendUri();

                $oAuthParams = $this->getOauthParams();
                $parameter = array_merge($oAuthParams, array(
                    "oauth_callback" => $url
                ));

                $requestUrl = "https://www.flickr.com/services/oauth/request_token";
                $this->sign($requestUrl, $parameter);

                $data = $this->httpRequest($requestUrl, $parameter);
                parse_str($data, $tokenArray);
                $oauthToken = $tokenArray['oauth_token'];
                $oauthTokenSecret = $tokenArray['oauth_token_secret'];
                setcookie("secret", $oauthTokenSecret);

                $accessurl = "https://www.flickr.com/services/oauth/authorize?oauth_token=$oauthToken";
                $this->view->assign('accessurl', $accessurl);
                $this->view->assign('name', $pagename);
            } else {
                $this->view->assign('name', $_GET["name"]);
            }
        }
    }


    /**
     * Make an HTTP request
     *
     * @param string $url
     * @param array $parameters
     * @return mixed
     */
    private function httpRequest($url, $parameters)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        if ($this->method == 'POST') {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        } else {
            // Assume GET
            curl_setopt($curl, CURLOPT_URL, "$url?" . $this->joinParameters($parameters));
        }
        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);
        $this->lastHttpResponseCode = $headers['http_code'];
        return $response;
    }

    /**
     * Join an array of parameters together into a URL-encoded string
     *
     * @param array $parameters
     * @return string
     */
    private function joinParameters($parameters)
    {
        $keys = array_keys($parameters);
        sort($keys, SORT_STRING);
        $keyValuePairs = array();
        foreach ($keys as $k) {
            array_push($keyValuePairs, rawurlencode($k) . "=" . rawurlencode($parameters[$k]));
        }
        return implode("&", $keyValuePairs);
    }

    /**
     * Get the base string for creating an OAuth signature
     *
     * @param string $method
     * @param string $url
     * @param array $parameters
     * @return string
     */
    private function getBaseString($method, $url, $parameters)
    {
        $components = array(
            rawurlencode($method),
            rawurlencode($url),
            rawurlencode($this->joinParameters($parameters))
        );
        $baseString = implode("&", $components);
        return $baseString;
    }

    /**
     * Sign an array of parameters with an OAuth signature
     *
     * @param string $url
     * @param array $parameters
     */
    private function sign($url, &$parameters, $page = null)
    {
        $baseString = $this->getBaseString($this->method, $url, $parameters);
        $signature = $this->getSignature($baseString, $page);
        $parameters['oauth_signature'] = $signature;
    }

    /**
     * Calculate the signature for a string
     *
     * @param string $string
     * @return string
     */
    private function getSignature($string, $page = null)
    {
        $keyPart1 = $this->flickrappsecret;
        $keyPart2 = "";
        if ($page != null) {
            $keyPart2 = $page->getTokensecret();
        }
        if (empty($keyPart2)) {
            $keyPart2 = $_COOKIE["secret"];
        }
        if (empty($keyPart2)) {
            $keyPart2 = '';
        }
        $key = "$keyPart1&$keyPart2";
        return base64_encode(hash_hmac('sha1', $string, $key, true));
    }


    /**
     * Call a Flickr API method
     *
     * @param string $method The FLickr API method name
     * @param array $parameters The method parameters
     * @return mixed|null The response object
     */
    public function call($method, $parameters = NULL, $token)
    {
        $requestParams = ($parameters == NULL ? array() : $parameters);
        $requestParams['method'] = $method;
        $requestParams['format'] = 'php_serial';
        $requestParams = array_merge($requestParams, $this->getOauthParams());
        $requestParams['oauth_token'] = $token;
        $this->sign(self::API_ENDPOINT, $requestParams);
        $response = $this->httpRequest(self::API_ENDPOINT, $requestParams);
        return empty($response) ? NULL : unserialize($response);
    }

    private function getOauthParams()
    {
        $params = array(
            'oauth_nonce' => $this->makeNonce(),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $this->flickrappid,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0',
        );
        return $params;
    }

    /**
     * Create a nonce
     *
     * @return string
     */
    private function makeNonce()
    {
        // Create a string that will be unique for this app and this user at this time
        $reasonablyDistinctiveString = implode(':',
            array(
                $this->flickrappid,
                microtime()
            )
        );
        return md5($reasonablyDistinctiveString);
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

            $dbpage = $this->pageRepository->searchByName($page->getName(), $this->streamtype);
            if ($dbpage->toArray()) {
                $already = 1;
                $page = $dbpage[0];
            }

            $storageRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $clear = 0;
            $storage = $storageRepository->findByUid('1');
            if ($storage->hasFolder("flickr")) {
                $targetFolder = $storage->getFolder('flickr');
            } else {
                $targetFolder = $storage->createFolder('flickr');
            }

            try {
                // ### get Page Data ###
                $page = $this->pageProcess($page, $storage, $targetFolder, $already);
                if ($targetFolder->hasFolder($page->getId())) {
                    $subFolder = $targetFolder->getSubfolder($page->getId());
                } else {
                    $subFolder = $targetFolder->createFolder($page->getId());
                }

                // ### get Gallery Page ###
                if ($subFolder->hasFolder("gallery")) {
                    $galleryFolder = $subFolder->getSubfolder("gallery");
                } else {
                    $galleryFolder = $subFolder->createFolder("gallery");
                }
                $clear += $this->galleryProcess($page, $storage, $targetFolder, $subFolder, $galleryFolder, $short);

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
     * action getFlickr
     *
     * @return void
     */
    public function getFlickrAction()
    {
        $this->initTSFE($this->rootPage, 0);
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $this->settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Socialstream');
        $this->pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Socialstream\\SocialStream\\Domain\\Repository\\PageRepository');
        $this->postRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Socialstream\\SocialStream\\Domain\\Repository\\PostRepository');
        $this->galleryRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Socialstream\\SocialStream\\Domain\\Repository\\GalleryRepository');
        $this->initializeAction();
        $short = 0;
        $pages = $this->pageRepository->findAll();
        $clear = 0;

        foreach ($pages as $page) {
            $storageRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
            $storage = $storageRepository->findByUid('1');
            if ($storage->hasFolder("flickr")) {
                $targetFolder = $storage->getFolder('flickr');
            } else {
                $targetFolder = $storage->createFolder('flickr');
            }

            try {
                // ### get Page Data ###
                $page = $this->pageProcess($page, $storage, $targetFolder, 1, 0);
                if ($targetFolder->hasFolder($page->getId())) {
                    $subFolder = $targetFolder->getSubfolder($page->getId());
                } else {
                    $subFolder = $targetFolder->createFolder($page->getId());
                }

                // ### get Gallery Page ###
                if ($subFolder->hasFolder("gallery")) {
                    $galleryFolder = $subFolder->getSubfolder("gallery");
                } else {
                    $galleryFolder = $subFolder->createFolder("gallery");
                }
                $clear += $this->galleryProcess($page, $storage, $targetFolder, $subFolder, $galleryFolder, $short);

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

        if (!$this->flickrappid || !$this->flickrappsecret) {
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
        if (!$tk) {
            $tk = $this->flickrappid . "|" . $this->flickrappsecret;
        }
        try {
            if ($page->getId()) {
                $elem = $this->call("flickr.people.getInfo", array("user_id" => $page->getId()), $tk);
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

        $helppage = $this->pageRepository->searchById($elem['person']['nsid'], $this->streamtype);
        if ($helppage) {
            $helppage->setToken($page->getToken());
            $page = $helppage;
            $already = 1;
        }


        $page->setId($elem['person']['nsid']);
        $page->setName($elem['person']['realname']['_content']);
        $page->setLink($elem['person']['profileurl']['_content']);
        $page->setStreamtype($this->streamtype);

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

        return $page;
    }

    private function galleryProcess($page, $storage, $targetFolder, $subFolder, $galleryFolder, $short, $paging = "")
    {
        $persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
        $tk = $page->getToken();
        $stream = $this->call("flickr.photosets.getList", array("user_id" => $page->getId()), $tk);

        foreach ($stream['photosets']['photoset'] as $entry) {
            $gallery = $this->galleryRepository->findHiddenById($entry['id'], $page->getUid());
            if ($gallery->toArray()) {
                $galleryalready = 1;
                $gallery = $gallery[0];
            } else {
                $gallery = new \Socialstream\SocialStream\Domain\Model\Gallery();
                $galleryalready = 0;
            }
            $gallery->setId($entry['id']);
            $photoset = $this->call("flickr.photosets.getPhotos", array("photoset_id"=>$entry['id'], "user_id" => $page->getId(), 'per_page' => 999), $tk);
            $images = $photoset['photoset']['photo'];
            $key = array_search(1, array_column($images, 'isprimary'));
            if (!$key){
                $key = 0;
            }
            $photo = $this->call("flickr.photos.getSizes", array("photo_id"=>$images[$key]['id'], "user_id" => $page->getId()), $tk);

            $photoKey = array_search("Large", array_column($photo['sizes']['size'], 'label'));
            $bildurl = $photo['sizes']['size'][$photoKey]['source'];
            $gallery->setPictureUrl($bildurl);
            $gallery->setCreatedTime((new \DateTime())->setTimestamp($entry['date_create']));
            $gallery->setTitle($entry['title']['_content']);
            $gallery->setDescription($entry['description']['_content']);
            $gallery->setGalleryUrl("https://www.flickr.com/photos/".$page->getId()."/sets/".$entry['id']);
            $gallery->setPage($page);

            if ($galleryalready) {
                $this->galleryRepository->update($gallery);
            } else {
                $this->galleryRepository->add($gallery);
                $clear = 1;
            }
            $persistenceManager->persistAll();

            $bild = NULL;
            $bildurl = $gallery->getPictureUrl();
            $bildname = $entry['id'] . ".jpg";

            if((!$galleryFolder->hasFile($bildname) && $bildname) || ($storage->getFileInFolder($bildname,$galleryFolder)->getSize() <= 0 && $galleryFolder->hasFile($bildname) && $bildname)) {
                if ($this->exists($bildurl)) {
                    $this->grab_image($bildurl, $this->tmp . $bildname);
                    $movedNewFile = $storage->addFile($this->tmp . $bildname, $galleryFolder, $bildname, \TYPO3\CMS\Core\Resource\DuplicationBehavior::REPLACE);

                    $bild = $movedNewFile->getUid();
                }
                if ($gallery->getPicture()) {
                    $GLOBALS["TYPO3_DB"]->exec_UPDATEquery("sys_file_reference", "uid=" . $gallery->getPicture()->getUid(), array('deleted' => '1'));
                }
            } elseif (!$gallery->getPicture() && $bildname) {
                $bild = $storage->getFile("/" . $targetFolder->getName() . "/" . $subFolder->getName() . "/" . $galleryFolder->getName() . "/" . $bildname);
                $bild = $bild->getUid();
            }
            if ($bild) {
                $data = array();
                $data['sys_file_reference']['NEW123456'] = array(
                    'uid_local' => $bild,
                    'uid_foreign' => $gallery->getUid(), // uid of your content record
                    'tablenames' => '	tx_socialstream_domain_model_gallery',
                    'fieldname' => 'picture',
                    'pid' => $this->storagePid, // parent id of the parent page
                    'table_local' => 'sys_file',
                );
                $data['tx_socialstream_domain_model_gallery'][$gallery->getUid()] = array('picture' => 'NEW123456'); // set to the number of images?

                /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $tce */
                $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler'); // create TCE instance
                $tce->bypassAccessCheckForRecords = TRUE;
                $tce->start($data, array());
                $tce->admin = TRUE;
                $tce->process_datamap();
                $clear = 1;
            }

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