<?php

declare(strict_types=1);

namespace App\Core;

final class I18n
{
    private const SUPPORTED = ['ko', 'en', 'ja'];

    /** @var array<string, array<string, string>> */
    private const MESSAGES = [
        'ko' => [
            'nav.primary' => '주요 기능', 'nav.retrospect' => '회고', 'nav.routine' => '루틴',
            'nav.todo' => '할 일', 'nav.today' => '오늘', 'nav.dashboard' => '대시보드',
            'nav.manage' => '관리', 'nav.plan' => '계획', 'nav.routine_manage' => '루틴 관리',
            'nav.execution_energy' => '실행력', 'nav.execution_energy_manage' => '실행력 관리',
            'nav.goal' => '목표', 'nav.tools' => '도구', 'nav.memo' => '메모', 'nav.tags' => '일정 태그 관리',
            'common.settings' => '설정', 'common.close' => '닫기', 'common.cancel' => '취소',
            'common.confirm' => '확인', 'common.menu_open' => '메뉴 열기', 'common.menu_close' => '메뉴 닫기',
            'common.signed_in' => '로그인 중',
            'settings.title' => '설정', 'settings.language' => '표시 언어',
            'settings.language_help' => '메뉴와 앱의 고정 문구에 사용할 언어입니다.',
            'settings.region' => '지역 및 시간대', 'settings.timezone' => '시간대',
            'settings.timezone_help' => '오늘 날짜, 루틴, 회고와 알림 시간을 계산하는 기준입니다.',
            'settings.display' => '화면 모드', 'settings.light' => '라이트 모드', 'settings.dark' => '다크 모드',
            'settings.display_save' => '화면 모드 저장', 'settings.notifications' => '알림 설정',
            'settings.permission' => '앱 알림 권한 요청', 'settings.test_notification' => '테스트 알림 보내기',
            'settings.push_enabled' => '앱 푸시 알림 사용', 'settings.morning_review' => '아침 회고 알림',
            'settings.evening_review' => '저녁 회고 알림', 'settings.routine_reminder' => '선택 루틴 알림',
            'settings.plan_reminder' => '캘린더 선택 계획 시작 알림', 'settings.goal_deadline' => '목표 마감 알림',
            'settings.goal_day_before' => '목표 마감 하루 전 알림', 'settings.notification_save' => '알림 설정 저장',
            'settings.notification_guide' => '알림 권한 안내 보기', 'settings.account' => '정책 및 계정 관리',
            'settings.privacy' => '개인정보처리방침', 'settings.terms' => '이용약관',
            'settings.contact' => '문의하기', 'settings.logout' => '로그아웃', 'settings.withdraw' => '회원탈퇴',
            'settings.save_preferences' => '언어 및 지역 설정 저장',
            'settings.saved_preferences' => '언어 및 지역 설정을 저장했습니다.',
            'settings.invalid_locale' => '지원하지 않는 언어입니다.',
            'settings.invalid_timezone' => '올바른 시간대를 선택해주세요.',
            'todo.title' => '오늘의 할 일', 'todo.empty' => '오늘 등록된 할 일이 없습니다.',
            'todo.empty_help' => '오늘 화면에서 계획을 추가하거나 계획 템플릿을 연결해보세요.',
            'todo.add' => '오늘 화면에서 추가', 'todo.complete' => '완료', 'todo.completed' => '완료됨',
            'todo.goal' => '목표 · {goal}', 'todo.untimed' => '시간 미정',
            'todo.time_title' => '실제 실행 시간',
            'todo.time_help' => '실제 일정과 겹치지 않는 시간을 선택하면 완료로 연결됩니다.',
            'todo.start' => '시작시간', 'todo.end' => '종료시간',
            'todo.save_complete' => '실제 일정 등록 및 완료',
            'todo.completed_message' => '실제 일정을 등록하고 할 일을 완료했습니다.',
            'todo.already_completed' => '이미 완료된 할 일입니다.',
            'todo.need_time' => '실제 실행 시간을 선택해주세요.',
            'todo.time_conflict' => '기존 실제 일정과 시간이 겹칩니다. 다른 시간을 선택해주세요.',
            'todo.invalid' => '할 일을 완료하지 못했습니다.',
            'routine.today_title' => '오늘의 루틴', 'routine.today_empty' => '오늘 실행할 루틴이 없습니다.',
            'routine.manage_link' => '루틴 관리',
            'calendar.todo_shortcut' => '오늘의 할 일', 'calendar.routine_shortcut' => '오늘의 루틴',
            'notification.review' => '회고 알림입니다', 'notification.routine_title' => '루틴 알림',
            'notification.routine' => '{name} 루틴을 확인할 시간입니다.', 'notification.plan_title' => '계획 알림',
            'notification.plan' => '{title} 시작 5분 전입니다.', 'notification.goal_title' => '목표 알림',
            'notification.goal_day_before' => '목표 마감일이 하루 남았습니다.',
            'notification.goal_deadline' => '목표 마감일입니다. 고생하셨습니다.',
            'retrospect.future_title' => '아직 오지 않은 하루예요',
            'retrospect.future_message_1' => '당신의 내일이 언제나 희망으로 차오르길 응원할게요.',
            'retrospect.future_message_2' => '아직 쓰이지 않은 하루가 당신다운 빛으로 채워지길 바라요.',
            'retrospect.future_message_3' => '오늘의 작은 선택이 내일의 기분 좋은 변화를 만들 거예요.',
            'retrospect.future_message_4' => '좋은 일이 기다리는 내일을 향해, 천천히 나아가요.',
        ],
        'en' => [
            'nav.primary' => 'Primary navigation', 'nav.retrospect' => 'Review', 'nav.routine' => 'Routine',
            'nav.todo' => 'To-do', 'nav.today' => 'Today', 'nav.dashboard' => 'Dashboard',
            'nav.manage' => 'Manage', 'nav.plan' => 'Plans', 'nav.routine_manage' => 'Manage routines',
            'nav.execution_energy' => 'Energy', 'nav.execution_energy_manage' => 'Manage energy',
            'nav.goal' => 'Goals', 'nav.tools' => 'Tools', 'nav.memo' => 'Memos', 'nav.tags' => 'Schedule tags',
            'common.settings' => 'Settings', 'common.close' => 'Close', 'common.cancel' => 'Cancel',
            'common.confirm' => 'Confirm', 'common.menu_open' => 'Open menu', 'common.menu_close' => 'Close menu',
            'common.signed_in' => 'Signed in',
            'settings.title' => 'Settings', 'settings.language' => 'Display language',
            'settings.language_help' => 'The language used for menus and fixed app text.',
            'settings.region' => 'Region and time zone', 'settings.timezone' => 'Time zone',
            'settings.timezone_help' => 'Used to calculate today, routines, reviews, and notification times.',
            'settings.display' => 'Appearance', 'settings.light' => 'Light mode', 'settings.dark' => 'Dark mode',
            'settings.display_save' => 'Save appearance', 'settings.notifications' => 'Notifications',
            'settings.permission' => 'Request app notification permission', 'settings.test_notification' => 'Send test notification',
            'settings.push_enabled' => 'Enable push notifications', 'settings.morning_review' => 'Morning review reminder',
            'settings.evening_review' => 'Evening review reminder', 'settings.routine_reminder' => 'Routine reminder',
            'settings.plan_reminder' => 'Selected plan start reminder', 'settings.goal_deadline' => 'Goal deadline reminder',
            'settings.goal_day_before' => 'Remind one day before goal deadline', 'settings.notification_save' => 'Save notification settings',
            'settings.notification_guide' => 'View notification permission guide', 'settings.account' => 'Policies and account',
            'settings.privacy' => 'Privacy policy', 'settings.terms' => 'Terms of service',
            'settings.contact' => 'Contact us', 'settings.logout' => 'Log out', 'settings.withdraw' => 'Delete account',
            'settings.save_preferences' => 'Save language and region',
            'settings.saved_preferences' => 'Language and region settings saved.',
            'settings.invalid_locale' => 'This language is not supported.',
            'settings.invalid_timezone' => 'Select a valid time zone.',
            'todo.title' => "Today's to-do list", 'todo.empty' => 'No to-do items are scheduled for today.',
            'todo.empty_help' => 'Add a plan or connect a plan template from Today.',
            'todo.add' => 'Add from Today', 'todo.complete' => 'Complete', 'todo.completed' => 'Completed',
            'todo.goal' => 'Goal · {goal}', 'todo.untimed' => 'Time not set',
            'todo.time_title' => 'Actual time',
            'todo.time_help' => 'Choose a time that does not overlap another actual event.',
            'todo.start' => 'Start', 'todo.end' => 'End',
            'todo.save_complete' => 'Add actual event and complete',
            'todo.completed_message' => 'Actual event added and to-do completed.',
            'todo.already_completed' => 'This to-do is already completed.',
            'todo.need_time' => 'Choose the actual time.',
            'todo.time_conflict' => 'This overlaps another actual event. Choose another time.',
            'todo.invalid' => 'Could not complete the to-do.',
            'routine.today_title' => "Today's routines", 'routine.today_empty' => 'No routines are scheduled for today.',
            'routine.manage_link' => 'Manage routines',
            'calendar.todo_shortcut' => "Today's to-do list", 'calendar.routine_shortcut' => "Today's routines",
            'notification.review' => 'Time for your review.', 'notification.routine_title' => 'Routine reminder',
            'notification.routine' => 'Time to check {name}.', 'notification.plan_title' => 'Plan reminder',
            'notification.plan' => '{title} starts in 5 minutes.', 'notification.goal_title' => 'Goal reminder',
            'notification.goal_day_before' => 'Your goal deadline is tomorrow.',
            'notification.goal_deadline' => 'Your goal is due today. Great work.',
            'retrospect.future_title' => 'This day is still ahead of you',
            'retrospect.future_message_1' => 'May your tomorrow always fill with hope.',
            'retrospect.future_message_2' => 'May this unwritten day be filled with your own light.',
            'retrospect.future_message_3' => 'A small choice today can create a joyful change tomorrow.',
            'retrospect.future_message_4' => 'Take your time as you move toward the good things waiting tomorrow.',
        ],
        'ja' => [
            'nav.primary' => 'メインメニュー', 'nav.retrospect' => '振り返り', 'nav.routine' => 'ルーティン',
            'nav.todo' => 'やること', 'nav.today' => '今日', 'nav.dashboard' => 'ダッシュボード',
            'nav.manage' => '管理', 'nav.plan' => '計画', 'nav.routine_manage' => 'ルーティン管理',
            'nav.execution_energy' => '実行力', 'nav.execution_energy_manage' => '実行力管理',
            'nav.goal' => '目標', 'nav.tools' => 'ツール', 'nav.memo' => 'メモ', 'nav.tags' => '予定タグ管理',
            'common.settings' => '設定', 'common.close' => '閉じる', 'common.cancel' => 'キャンセル',
            'common.confirm' => '確認', 'common.menu_open' => 'メニューを開く', 'common.menu_close' => 'メニューを閉じる',
            'common.signed_in' => 'ログイン中',
            'settings.title' => '設定', 'settings.language' => '表示言語',
            'settings.language_help' => 'メニューとアプリの固定文言に使用する言語です。',
            'settings.region' => '地域とタイムゾーン', 'settings.timezone' => 'タイムゾーン',
            'settings.timezone_help' => '今日、ルーティン、振り返り、通知時刻の計算基準です。',
            'settings.display' => '表示モード', 'settings.light' => 'ライトモード', 'settings.dark' => 'ダークモード',
            'settings.display_save' => '表示モードを保存', 'settings.notifications' => '通知設定',
            'settings.permission' => 'アプリの通知権限をリクエスト', 'settings.test_notification' => 'テスト通知を送信',
            'settings.push_enabled' => 'プッシュ通知を使用', 'settings.morning_review' => '朝の振り返り通知',
            'settings.evening_review' => '夜の振り返り通知', 'settings.routine_reminder' => 'ルーティン通知',
            'settings.plan_reminder' => '選択した計画の開始通知', 'settings.goal_deadline' => '目標期限の通知',
            'settings.goal_day_before' => '目標期限の前日通知', 'settings.notification_save' => '通知設定を保存',
            'settings.notification_guide' => '通知権限ガイドを見る', 'settings.account' => 'ポリシーとアカウント管理',
            'settings.privacy' => 'プライバシーポリシー', 'settings.terms' => '利用規約',
            'settings.contact' => 'お問い合わせ', 'settings.logout' => 'ログアウト', 'settings.withdraw' => '退会',
            'settings.save_preferences' => '言語と地域設定を保存',
            'settings.saved_preferences' => '言語と地域設定を保存しました。',
            'settings.invalid_locale' => '対応していない言語です。',
            'settings.invalid_timezone' => '正しいタイムゾーンを選択してください。',
            'todo.title' => '今日のやること', 'todo.empty' => '今日のやることは登録されていません。',
            'todo.empty_help' => '今日画面から計画を追加するか、計画テンプレートを連携してください。',
            'todo.add' => '今日画面で追加', 'todo.complete' => '完了', 'todo.completed' => '完了済み',
            'todo.goal' => '目標・{goal}', 'todo.untimed' => '時間未定',
            'todo.time_title' => '実行時間',
            'todo.time_help' => '他の実績予定と重ならない時間を選択してください。',
            'todo.start' => '開始時刻', 'todo.end' => '終了時刻',
            'todo.save_complete' => '実績予定を登録して完了',
            'todo.completed_message' => '実績予定を登録し、やることを完了しました。',
            'todo.already_completed' => 'すでに完了しています。',
            'todo.need_time' => '実際の実行時間を選択してください。',
            'todo.time_conflict' => '他の実績予定と時間が重なっています。別の時間を選択してください。',
            'todo.invalid' => '完了できませんでした。',
            'routine.today_title' => '今日のルーティン', 'routine.today_empty' => '今日実行するルーティンはありません。',
            'routine.manage_link' => 'ルーティン管理',
            'calendar.todo_shortcut' => '今日のやること', 'calendar.routine_shortcut' => '今日のルーティン',
            'notification.review' => '振り返りの時間です。', 'notification.routine_title' => 'ルーティン通知',
            'notification.routine' => '{name}を確認する時間です。', 'notification.plan_title' => '計画通知',
            'notification.plan' => '{title}の開始5分前です。', 'notification.goal_title' => '目標通知',
            'notification.goal_day_before' => '目標期限まであと1日です。',
            'notification.goal_deadline' => '今日は目標期限です。お疲れさまでした。',
            'retrospect.future_title' => 'まだ訪れていない一日です',
            'retrospect.future_message_1' => 'あなたの明日がいつも希望で満ちますように。',
            'retrospect.future_message_2' => 'まだ書かれていない一日が、あなたらしい光で満たされますように。',
            'retrospect.future_message_3' => '今日の小さな選択が、明日のうれしい変化につながります。',
            'retrospect.future_message_4' => '明日待っている素敵なことへ、ゆっくり進んでいきましょう。',
        ],
    ];

    public static function normalizeLocale(?string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? (string) $locale : 'ko';
    }

    /** @return array<int, string> */
    public static function supportedLocales(): array
    {
        return self::SUPPORTED;
    }

    /** @param array<string, scalar|null> $replace */
    public static function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = self::normalizeLocale($locale ?? (string) ($_SESSION['locale_preference'] ?? 'ko'));
        $message = self::MESSAGES[$locale][$key] ?? self::MESSAGES['ko'][$key] ?? $key;
        foreach ($replace as $name => $value) {
            $message = str_replace('{' . $name . '}', (string) $value, $message);
        }

        return $message;
    }
}
