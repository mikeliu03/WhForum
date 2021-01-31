<?php
return array (
  'config'=>[
      'type' => array (
          'title' => '开启类型',
          'type' => 'radio',
          'value' => 'wangeditor',
          'options' => array (
              'wangeditor' => '富文本编辑器',
              'markdown' => 'Markdown编辑器',
              'all' => '同时使用',
          ),
      ),
      'fileMaxSize'=>[
          'title' => '最大上传限制(M)',
          'type' => 'text',
          'value' => '10',
          'tips' => '' //提示
      ],
      'timeout'=>[
          'title' => '超时时间',
          'type' => 'text',
          'value' => '30000',
          'tips' => '' //提示
      ],
  ],
  'name' => 'editor',
  'title' => '富文本/Markdown编辑器',
  'intro' => '可随意切换编辑器为Markdown和富文本编辑器',
  'author' => 'WeCenter官方开发团队',
  'version' => '3.6.0',
  'state' => 2,
);