<?php

namespace Micx\FormMailer\Config;

class Config
{

    public function __construct(

        /**
         * @var string
         */
        public $subscription_id,

        /**
         * @var string[]
         */
        public $allow_origins,

        /**
         * @var string
         */
        public $template_url

    ){}

}
