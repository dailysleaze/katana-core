<?php

namespace Katana\FileHandlers;

use Symfony\Component\Finder\SplFileInfo;
use Katana\Markdown;

class BlogPostHandler extends BaseHandler
{
    /**
     * Get the blog post data.
     *
     * @param SplFileInfo $file
     *
     * @return \stdClass
     */
    public function getPostData(SplFileInfo $file)
    {
        $this->file = $file;

        if ($this->file->getExtension() == 'md') {
            $postData = Markdown::parseWithYAML($this->file->getContents())[1];
        } else {
            $view = $this->viewFactory->make(str_replace('.blade.php', '', $this->file->getRelativePathname()));

            $postData = [];

            $view->render(function ($view) use (&$postData) {
                $postData = $view->getFactory()->getSections();
            });
        }

        // Get only values with keys starting with post::
        $postData = array_where($postData, function ($key) {
            return starts_with($key, 'post::');
        });

        // Remove 'post::' from $postData keys
        foreach ($postData as $key => $val) {
            $postData[str_replace('post::', '', $key)] = $val;

            unset($postData[$key]);
        }

        $postData['path'] = str_replace(KATANA_PUBLIC_DIR, '', $this->getDirectoryPrettyName());

        return json_decode(json_encode($postData), false);
    }

    /**
     * Generate directory path to be used for pretty URLs.
     *
     * @return string
     */
    protected function getDirectoryPrettyName()
    {
        if (str_is('*/_blog/*/*', $this->file->getPathname())) {
            return str_replace('/_blog', '', parent::getDirectoryPrettyName());
        }

        $fileBaseName = $this->getFileName();

        $fileRelativePath = $this->getBlogPostSlug($fileBaseName);

        return KATANA_PUBLIC_DIR."/$fileRelativePath";
    }

    /**
     * Generate blog post slug.
     *
     * @param string $fileBaseName
     *
     * @return string
     */
    private function getBlogPostSlug($fileBaseName)
    {
        preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)/', $fileBaseName, $matches);

        return $matches[2].'-'.str_replace('-', '', $matches[1]);
    }
}