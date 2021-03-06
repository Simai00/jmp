<?php

namespace jmp\Models;


class EventType implements ArrayConvertable
{
    use ArrayConvertableTrait;
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $color;

    /**
     * EventType constructor.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->id = (int)$args['id'];
        $this->title = $args['title'];
        $this->color = $args['color'];
    }
}