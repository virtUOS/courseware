<?
namespace Mooc\UI\HtmlBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

// TODO: lots!
class HtmlBlock extends Block
{
    const NAME = 'Freitext';

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        $this->setGrade(1.0);
        return array('content' => $this->content);
    }


    function author_view()
    {
        return $this->toJSON();
    }

    /**
     * {@inheritdoc}
     */
    public function exportContents()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        /** @var \Seminar_User $user */
        global $user;

        $files = array();
        $crawler = new Crawler($this->content);

        // extract a file id from a URL
        $extractFile = function ($url) use ($user) {
            $components = parse_url($url);

            if (
                isset($components['host'])
                && $components['host'] === $_SERVER['SERVER_NAME']
                && isset($components['path'])
                && substr($components['path'], -13) == '/sendfile.php'
                && isset($components['query'])
                && $components['query'] != ''
            ) {
                parse_str($components['query'], $queryParams);

                if (isset($queryParams['file_id'])) {
                    $document = new \StudipDocument($queryParams['file_id']);

                    if (!$document->checkAccess($user->cfg->getUserId())) {
                        return null;
                    }

                    return array(
                        'id' => $queryParams['file_id'],
                        'filename' => $document->filename,
                        'path' => get_upload_file_path($queryParams['file_id']),
                    );
                }
            }

            return null;
        };

        // filter files referenced in anchor elements
        $crawler->filterXPath('//a')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('href'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        // filter files referenced in image elements
        $crawler->filterXPath('//img')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('src'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function importContents($contents)
    {
        $this->content = $contents;
        $this->save();
    }
}
