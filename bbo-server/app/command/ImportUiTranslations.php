<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use app\common\model\UiTranslation;

/**
 * 导入 UI 翻译命令
 * 将前端 JSON 翻译文件导入数据库
 *
 * 使用方式:
 *   php think ui:import                    # 导入所有语言
 *   php think ui:import --locale=ko-KR     # 导入指定语言
 *   php think ui:import --clear            # 清空后重新导入
 */
class ImportUiTranslations extends Command
{
    // 前端翻译文件路径（相对于项目根目录）
    protected $localeBasePath = '../bbo-app/src/locale';

    // 语言代码映射（目录名 => 数据库存储格式）
    protected $localeMapping = [
        'zh-TW' => 'zh-tw',
        'en-US' => 'en-us',
        'ja-JP' => 'ja-jp',
    ];

    protected function configure()
    {
        $this->setName('ui:import')
            ->setDescription('导入前端 UI 翻译文件到数据库')
            ->addOption('locale', 'l', Option::VALUE_OPTIONAL, '指定语言代码，如 zh-TW')
            ->addOption('clear', 'c', Option::VALUE_NONE, '清空现有翻译后重新导入');
    }

    protected function execute(Input $input, Output $output)
    {
        $specifiedLocale = $input->getOption('locale');
        $clearFirst = $input->getOption('clear');

        $basePath = app()->getRootPath() . $this->localeBasePath;

        if (!is_dir($basePath)) {
            $output->writeln('<error>翻译文件目录不存在: ' . $basePath . '</error>');
            return 1;
        }

        $totalImported = 0;

        foreach ($this->localeMapping as $dirName => $dbLocale) {
            // 如果指定了语言，只处理该语言
            if ($specifiedLocale && $specifiedLocale !== $dirName && $specifiedLocale !== $dbLocale) {
                continue;
            }

            $localePath = $basePath . '/' . $dirName;
            if (!is_dir($localePath)) {
                $output->writeln("<comment>跳过不存在的目录: {$localePath}</comment>");
                continue;
            }

            $output->writeln("<info>处理语言: {$dirName} -> {$dbLocale}</info>");

            // 如果需要清空
            if ($clearFirst) {
                $deleted = UiTranslation::deleteByLocale($dbLocale);
                $output->writeln("  清空现有翻译: {$deleted} 条");
            }

            // 是否是原始语言（英语）
            $isOriginal = ($dbLocale === 'en-us');

            // 遍历目录下的所有 JSON 文件
            $files = glob($localePath . '/*.json');
            foreach ($files as $file) {
                $namespace = pathinfo($file, PATHINFO_FILENAME);
                $content = file_get_contents($file);
                $translations = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $output->writeln("<error>  JSON 解析错误: {$file}</error>");
                    continue;
                }

                $count = UiTranslation::importTranslations($dbLocale, $namespace, $translations, $isOriginal);
                $output->writeln("  导入 {$namespace}: {$count} 条");
                $totalImported += $count;
            }
        }

        $output->writeln("<info>导入完成，共 {$totalImported} 条翻译</info>");
        return 0;
    }
}
