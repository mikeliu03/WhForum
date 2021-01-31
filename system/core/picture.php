<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/
require_once AWS_PATH.'Services/Picture/src/autoloader.php';
use Grafika\Grafika;
use Grafika\Color;
class core_picture
{
	protected $editor;

	public function __construct()
	{
		$this->editor = Grafika::createEditor();
	}

    /**
     * 图像裁切
     * @param string $source_img 原图片
     * @param string $out_img 输出图片
     * @param int $width 缩略图宽度
     * @param int $height 缩略图高度
     * @param string $type 裁切类型
     * @return mixed
     */
	public function resize($source_img,$out_img,$width,$height,$type='auto')
	{
        $this->editor->open($image , $source_img);
        switch ($type)
        {
            //等比例缩放
            case 'fit':
                $this->editor->resizeFit($image , $width , $height);
                break;
            //固定比例缩放
            case 'exact':
                $this->editor->resizeExact($image , $width , $height);
                break;
            //居中裁切
            case 'fill':
                $this->editor->resizeFill($image , $width , $height);
                break;
            //等宽缩放
            case 'exact_width':
                $this->editor->resizeExactWidth($image , $width);
                break;
            //等宽缩放
            case 'exact_height':
                $this->editor->resizeExactHeight($image , $height);
                break;
            default:
                $this->editor->crop( $image, $width, $height, 'smart' );
                break;
        }
        $this->editor->save($image , $out_img);
        return $out_img;
	}
}