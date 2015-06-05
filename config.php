<?php
$config = array(
    //'master' => array(
        //'accessKey'         => 'AsEux5HcynPVTusx',
        //'secureKey'         => 'u0ghmqk8YdPf4wlOVXMpJ1ppS52lOx',
        //'bucket'            => 'guiping',
        //'host'              => 'oss-cn-beijing.aliyuncs.com',
    //),
    'master' => array(
            'accessKey'     => '2JiHyoSooDeTvZc5',
            'secureKey'     => 'BeeCaHVy1MbIHaylXomu4BHrue8TJJ',
            'bucket'        => 'lm02',
            'host'          => 'oss-cn-beijing.aliyuncs.com',
    ),
    'slaves' => array(
        'lm03' => array(
            'accessKey'     => '2JiHyoSooDeTvZc5',
            'secureKey'     => 'BeeCaHVy1MbIHaylXomu4BHrue8TJJ',
            'bucket'        => 'lm03',
            'host'          => 'oss-cn-beijing.aliyuncs.com',
        ),
        'lm04' => array(
            'accessKey'     => '2JiHyoSooDeTvZc5',
            'secureKey'     => 'BeeCaHVy1MbIHaylXomu4BHrue8TJJ',
            'bucket'        => 'lm04',
            'host'          => 'oss-cn-beijing.aliyuncs.com',
        ),
        'lm05' => array(
            'accessKey'     => '2JiHyoSooDeTvZc5',
            'secureKey'     => 'BeeCaHVy1MbIHaylXomu4BHrue8TJJ',
            'bucket'        => 'lm05',
            'host'          => 'oss-cn-beijing.aliyuncs.com',
        )
    ),
    'local' => array(
        'backdir'           => dirname(__FILE__).DIRECTORY_SEPARATOR.'tmp'
    ),
);
