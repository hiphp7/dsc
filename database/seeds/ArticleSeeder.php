<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\TimeRepository;

class ArticleSeeder extends Seeder
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DeleteFileSeeder constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }


    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run()
    {

        $this->crossBorderArticle();

    }

    /**
     * 新增支付密码启用开关 (购物流程配置)
     */
    private function crossBorderArticle()
    {
        $file = Storage::disk('local')->exists('seeder/article.lock.php');

        if (!$file) {
            $result = DB::table('article')->where('cat_id', '-2')->count();
            if (empty($result)) {

                // 默认数据
                $rows = [
                    [
                        'cat_id' => '-2',
                        'title' => '消费者告知书',
                        'content' => '<p>1、您承诺所购商品系个人合理自用且不进行二次销售。</p>
<p>2、您知晓在xxx网站上购买的境外商品等同于境外购买。</p>
<p>3、您知晓境外商品适用的品质、健康、安全、卫生、环保、标识等项目标准与中国大陆地区质量安全标准有所不同，在使用过程中由此可能产生的危害或损失以及其他风险，将由您个人承担。</p>
<p>4、您知晓商品的订购人（即支付人）将被记录为进口方，必须遵守中国的法律法规。</p>
<p>5、您知晓由于您所购买的境外商品从中国大陆（不含港澳台）以外的地区发出，故可能无中文标签及说明书。您可以通过商品详情页查看中文标签及说明，中文标签及说明是由英文直接翻译，若您对翻译后的中文标签有疑问，可联系商家确认。</p>
<p>6、您同意委托商家选定适合的物流商，并将您所购买的商品运输至您指定的中国大陆境内的收货地点。
您现委托商家或物流商办理申报、代缴税款等通关事宜，并承诺提供完整、真实、准确及有效的身份信息供商家或物流商办理商品清关手续。您同意在商家或物流商向海囤全球请求时，海囤全球可以向商家或物流商提供您的身份信息，供商家或物流商办理您购买商品的清关手续。勾选确认选择接受本须知，即表示您愿意接受本须知的内容。您应在接受前认真阅读本须知，如果您对本须知的内容有疑问或者不同意本须知的任一条款，请不要进行后续操作。</p>',
                        'add_time' => app(TimeRepository::class)->getGmTime(),
                        'article_type' => '0',
                        'is_open' => 1,
                    ]
                ];
                DB::table('article')->insert($rows);
            }

            $data = '大商创x https://www.dscmall.cn/';
            Storage::disk('local')->put('seeder/article.lock.php', $data);
        }

    }

}
