<?php

namespace Nextend\Framework\Browse;

use Exception;
use Nextend\Framework\Browse\BulletProof\BulletProof;
use Nextend\Framework\Controller\Admin\AdminAjaxController;
use Nextend\Framework\Filesystem\Filesystem;
use Nextend\Framework\Image\Image;
use Nextend\Framework\Notification\Notification;
use Nextend\Framework\Request\Request;
use Nextend\Framework\ResourceTranslator\ResourceTranslator;
use Joomla\CMS\Component\ComponentHelper;

class ControllerAjaxBrowse extends AdminAjaxController {

    public function actionIndex() {
        $this->validateToken();
        $requestedPath = Request::$REQUEST->getVar('path', '');

        $root = Filesystem::convertToRealDirectorySeparator(Filesystem::getImagesFolder());

        $originalFullPath = $root . DIRECTORY_SEPARATOR . ltrim(rtrim($requestedPath, '/'), '/');
        $path             = Filesystem::realpath($originalFullPath);

        if (strpos($path, $root) !== 0) {
            $path = $root;

            if ($requestedPath) {
                $isLinkDir = is_link($originalFullPath) ? true : false;
                if (!$isLinkDir) {
                    /**
                     * If the full path isn't a Symlink, then we should also check if one of the parent folders is a symlink or not.
                     */
                    $parentDir = $originalFullPath;
                    while (is_dir($parentDir) && $parentDir !== $root && !$isLinkDir) {
                        $parentDir = dirname($parentDir);
                        $isLinkDir = is_link($parentDir);
                    }
                }

                if ($isLinkDir) {
                    if (str_ends_with($requestedPath, '..')) {
                        /**
                         * Move one level up in a folder that is located inside a Symlink
                         */
                        $oneLevelUpRequestedPath = substr($requestedPath, 0, -2);
                        $path                    = dirname($root . DIRECTORY_SEPARATOR . ltrim(rtrim($oneLevelUpRequestedPath, '/'), '/'));
                    } else {
                        /**
                         * Get the Symlink path.
                         */
                        $path = $originalFullPath;
                    }
                }
            }
        }


        $_directories = glob($path . '/*', GLOB_ONLYDIR);
        $directories  = array();
        for ($i = 0; $i < count($_directories); $i++) {
            $directories[basename($_directories[$i])] = Filesystem::toLinux($this->relative($_directories[$i], $root));
        }

        $extensions = array(
            'jpg',
            'jpeg',
            'png',
            'gif',
            'mp4',
            'mp3',
            'svg',
            'webp'
        );
        $_files     = scandir($path);
        $files      = array();
        for ($i = 0; $i < count($_files); $i++) {
            $_files[$i] = $path . DIRECTORY_SEPARATOR . $_files[$i];
            $ext        = strtolower(pathinfo($_files[$i], PATHINFO_EXTENSION));
            if (self::check_utf8($_files[$i]) && in_array($ext, $extensions)) {
                $files[basename($_files[$i])] = ResourceTranslator::urlToResource(Filesystem::pathToAbsoluteURL($_files[$i]));
            }
        }
        $relativePath = Filesystem::toLinux($this->relative($path, $root));
        if (!$relativePath) {
            $relativePath = '';
        }
        $this->response->respond(array(
            'fullPath'    => $path,
            'path'        => $relativePath,
            'directories' => (object)$directories,
            'files'       => (object)$files
        ));
    }

    private static function check_utf8($str) {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c > 247)) return false; elseif ($c > 239) $bytes = 4;
                elseif ($c > 223) $bytes = 3;
                elseif ($c > 191) $bytes = 2;
                else return false;
                if (($i + $bytes) > $len) return false;
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bytes--;
                }
            }
        }

        return true;
    }

    private function displayError($error_code) {
        $errorMessages = array(
            1 => n2_('The uploaded file exceeds the upload_max_filesize directive in php.ini.'),
            2 => n2_('The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.'),
            3 => n2_('The uploaded file was only partially uploaded.'),
            4 => n2_('No file was uploaded.'),
            6 => n2_('The temporary folder is missing from the server.'),
            7 => n2_('Failed to write file to disk.'),
            8 => n2_('A PHP extension stopped the file uploading.')
        );

        if (isset($errorMessages[$error_code])) {
            $error = $errorMessages[$error_code];
        } else {
            $error = n2_('Unknown error code: ' . $error_code);
        }

        Notification::error(n2_('File uploading was stopped by the server!') . '<br>' . $error);
        $this->response->error();
    }

    public function actionUpload() {
        if (defined('N2_IMAGE_UPLOAD_DISABLE')) {
            Notification::error(n2_('You are not allowed to upload!'));
            $this->response->error();
        }
        if (!current_user_can('upload_files')) {
            Notification::error(n2_('You are not allowed to upload!'));
            $this->response->error();
        }
    

        $this->validateToken();

        $media = Request::$FILES->getVar('media');
        if ($media['error'] > 0) {
            $this->displayError($media['error']);
        }

        $mediaMime    = mime_content_type($media['tmp_name']);
        $allowedMimes = array(
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4'
        );
        if (!in_array($mediaMime, get_allowed_mime_types()) || !in_array($mediaMime, $allowedMimes)) {
            Notification::error(n2_('You are not allowed to upload this filetype!'));
            $this->response->error();
        }

        $requestedPath = Request::$REQUEST->getVar('path', '');

        $root             = Filesystem::getImagesFolder();
        $folder           = ltrim(rtrim($requestedPath, '/'), '/');
        $originalFullPath = $root . DIRECTORY_SEPARATOR . $folder;
        $path             = Filesystem::realpath($originalFullPath);


        if ($path === false || $path == '') {
            $folder = preg_replace("/[^A-Za-z0-9]/", '', $folder);
            if (empty($folder)) {
                Notification::error(n2_('Folder is missing!'));
                $this->response->error();
            } else {
                Filesystem::createFolder($root . '/' . $folder);
                $path = Filesystem::realpath($root . '/' . $folder);
            }
        }


        if (strpos($path, $root) !== 0) {
            if ($requestedPath) {
                $isLinkDir = is_link($originalFullPath) ? true : false;
                if (!$isLinkDir) {
                    /**
                     * If the full path isn't a Symlink, then we should also check if one of the parent folders is a symlink or not.
                     */
                    $parentDir = $originalFullPath;
                    while (is_dir($parentDir) && $parentDir !== $root && !$isLinkDir) {
                        $parentDir = dirname($parentDir);
                        $isLinkDir = is_link($parentDir);
                    }
                }

                if ($isLinkDir) {
                    /**
                     * Get the Symlink path.
                     */
                    $path = $originalFullPath;
                } else {
                    /**
                     * Block traversal
                     * @see SSDEV-4131
                     */
                    Notification::error(n2_('Invalid upload path!'));
                    $this->response->error();
                }
            }
        }

        $relativePath = Filesystem::toLinux($this->relative($path, $root));
        if (!$relativePath) {
            $relativePath = '';
        }
        $response = array(
            'path' => $relativePath
        );
        try {
            if ($media['name'] !== null) {
                $info = pathinfo($media['name']);

                if ($mediaMime != $allowedMimes[strtolower($info['extension'])]) {
                    Notification::error(n2_('You are not allowed to upload a file with different extension (' . $info['extension'] . ') and mime type(' . $mediaMime . ')!'));
                    $this->response->error();
                }

                $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '', $info['filename']);
                if (strlen($fileName) == 0) {
                    $fileName = '';
                }

                $upload           = new BulletProof();
                $file             = $upload->uploadDir($path)
                                           ->upload($media, $fileName);
                $response['name'] = basename($file);
                $response['url']  = ResourceTranslator::urlToResource(Filesystem::pathToAbsoluteURL($file));

                Image::onImageUploaded($file);
            }
        } catch (Exception $e) {
            Notification::error($e->getMessage());
            $this->response->error();
        }


        $this->response->respond($response);
    }

    private function str_starts_with($haystack, $needle) {
        if (!function_exists('str_starts_with')) {
            if ('' === $needle) {
                return true;
            }

            return 0 === strpos($haystack, $needle);
        } else {
            return str_starts_with($haystack, $needle);
        }
    }

    private function relative($path, $root) {
        return substr(Filesystem::convertToRealDirectorySeparator($path), strlen($root));
    }
}
