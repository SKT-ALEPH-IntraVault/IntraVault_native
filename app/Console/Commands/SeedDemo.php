<?php
namespace App\Console\Commands;

use App\Services\DemoDataset;
use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature='intravault:seed-demo {--credentials= : 신규 계정 정보를 저장할 비공개 JSON 파일 경로}';
    protected $description='승인된 4개 부서·13개 계정·24개 합성 문서를 한 번만 생성';

    public function handle(DemoDataset $dataset): int
    {
        $path=$this->option('credentials');
        if (!$dataset->installed() && (!$path || file_exists($path) || !is_writable(dirname($path)))) {
            $this->error('기존 파일을 덮어쓰지 않는 비공개 --credentials 경로를 지정하세요.');
            return self::FAILURE;
        }
        $result=$dataset->install();
        if ($result['already_installed']) {
            $this->info('이미 적용된 demo-v1입니다. 계정·문서·비밀번호·스위치를 변경하지 않았습니다.');
            return self::SUCCESS;
        }
        $old=umask(0077);
        try {
            if (file_put_contents($path,json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) {
                throw new \RuntimeException('계정 정보 파일 저장 실패. 관리자에서 신규 계정 비밀번호를 재발급하세요.');
            }
            chmod($path,0600);
        } finally { umask($old); }
        $this->info('더미데이터 적용 완료: 부서 4개, 계정 13개, 문서 24개, 부서 간 공유 4개. 기존 관리자 비밀번호와 취약점 스위치는 유지했습니다.');
        return self::SUCCESS;
    }
}
