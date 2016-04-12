<?
namespace Mooc\UI\WallNewspaperBlock\Model;

class Topic {

    public $id, $title, $content, $childTopics, $complete;

    public function __construct($id, $title, $content, $complete)
    {
        $this->id      = $id;
        $this->title   = $title;
        $this->content = $content;
        $this->childTopics = [];
        $this->complete = $complete;
    }

    public function addChildTopic($topic)
    {
        $this->childTopics[] = $topic;
    }

    public function toJSON($shallow = false)
    {
        $json = [
            'id' => (string) $this->id,
            'title' => studip_utf8encode($this->title),
            'complete' => !!$this->complete
        ];

        if (!$shallow) {
            $json['childTopics'] = array_map(function ($subtopic) { return $subtopic->toJSON(); }, $this->childTopics);
        }

        if ($this->content) {
            foreach ($this->content as $key => $value) {
                if (!isset($json[$key])) {
                    $json[$key] = studip_utf8encode($value);
                }
            }
        }

        return $json;
    }

    public function __toString()
    {
        return json_encode($this->toJSON(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
    }
}
