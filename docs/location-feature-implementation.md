# 위치 저장과 지도 표시

할 일 추가/수정 및 실제 일정 추가/수정은 공유 `location-fields.php`와 `location-fields.js`를 사용한다. 위치는 기본 접힘 행으로 표시하며 펼치면 주소·장소명 검색, 검색 결과 선택, 지도 마커, 장소명·주소, 제거·장소 변경·카카오 지도 열기를 제공한다. 선택/제거는 폼의 저장 버튼을 눌러야 반영된다. 검색어 입력만으로는 위치가 저장되지 않는다. 시트 안에서 검색하므로 검색용 시트를 중첩하지 않는다.

## 데이터와 권한

`saved_locations`의 (user_id, entity_type, entity_id)가 키이며 entity_type은 todo 또는 actual이다. 이름/주소/위도/경도를 저장한다. 할 일과 실제 일정 위치는 독립적이며 연결만으로 자동 복제하지 않는다. 기존 위치는 비어 있다. 로그인된 `GET /locations?type=todo|actual&id=...`는 소유권과 미삭제 상태를 확인하고 no-store를 사용한다. 쓰기는 기존 CSRF 보호 폼의 `location_json`으로 처리하며 일정 저장과 동일한 DB 트랜잭션에서 수행한다. 실패하면 일정 생성/수정도 롤백한다.

누락/빈 입력은 기존 위치 유지, JSON null은 제거, 객체는 새 위치 선택이다. 서버는 문자열 길이(장소 120자/주소 300자), 숫자 좌표와 유효 범위를 검사한다. URL은 저장하지 않고 검증한 좌표로 카카오맵 URL을 만든다. 검색 응답은 textContent로 표시한다. 다른 항목을 열거나 새 검색을 시작하면 늦게 도착한 이전 응답은 무시한다. 조회 실패 시 일반 저장은 기존 위치를 지우지 않는다. 소프트 삭제된 일정의 위치는 소유권 조회에서 접근 불가능해진다.

## 카카오 설정

`KAKAO_MAP_JS_KEY`는 .env에서 읽고 두 페이지에만 전달한다. .env.example은 값 없는 설정 이름만 유지한다. SDK는 검색 또는 저장 위치의 지도 열기 시 최초 로드하며 services 라이브러리와 autoload=false를 사용한다. Geocoder.addressSearch와 Places.keywordSearch 결과를 중복 제거해 최대 15개 제공한다. 지도는 level 3, 마커 1개이며 시트 스크롤을 방해하지 않도록 드래그/휠 확대를 끈다. 실제 지도와 출처 표기를 유지하며 실패 시 가짜 지도를 표시하지 않는다.

공식 문서: [시작/도메인 등록](https://apis.map.kakao.com/web/guide/), [검색 API](https://apis.map.kakao.com/web/documentation/).

2026-11-11 로컬 실제 호출 결과: HTTP 401 AccessDeniedError, domain mismatched, caller=http://localhost:8080. 키는 설정되어 있지만 현재 도메인이 허용되지 않았다. 카카오 개발자 콘솔의 해당 JavaScript 키에 http://localhost:8080과 실제 서비스 도메인을 등록해야 라이브 지도/검색을 검수할 수 있다. 키 원문은 문서에 기록하지 않았다.

## 마이그레이션

두 전체 스키마에 테이블을 포함한다. 기존 MySQL은 `sql/migration.saved_locations.mysql.sql`을 적용한다. CREATE TABLE IF NOT EXISTS이므로 반복 실행 가능하다. 기존 SQLite는 schema-object bootstrap에서 자동 생성하며 SQLite 마이그레이션도 제공한다. 로컬 개발 DB에는 이 테이블 생성만 적용했다. 기존 일정 행은 변경하지 않았다.

## 검증

- `tests/saved_locations.php`: 격리 SQLite에서 좌표/형식 검증, 소유권, Todo/Actual 생성·수정·제거, 두 서비스의 입력 전달, 일반 수정 시 위치 유지, 위치 저장 실패 시 소유 일정 롤백.
- 기존 STEP 0 회귀 50개 통과.
- 실제 SDK 호출은 위 도메인 제한으로 차단됨. 성공한 라이브 지도 검증으로 표시하지 않는다.
- SDK mock 브라우저 검사는 320/390/1024px iframe에서 밝은/어두운 카드 너비, 결과 선택·직렬화·제거, 다른 항목 조회 응답 역전, 오래된 검색 결과 무시, 폼 초기화 시 draft 제거를 확인했다. Mock 지도는 실제 타일 렌더링 검증이 아니다.

## 수동 확인

1. 카카오 도메인을 등록한 뒤 할 일 추가에서 위치를 펼쳐 주소 또는 카페 이름을 검색한다. 원하는 결과를 선택해 지도 마커/이름/주소를 확인하고 저장한다.
2. 해당 할 일을 다시 열어 저장 위치를 확인하고 장소 변경·제거를 각각 저장한다. 저장하지 않고 닫으면 원래 값이 남아야 한다.
3. 실제 일정 추가/수정에서도 같은 과정을 확인한다. 할 일 위치와 실제 일정 위치가 서로 덮어쓰이지 않아야 한다.
4. 검색 중 다른 일정을 열거나 연속 검색해 이전 응답이 새 위치를 덮어쓰지 않는지 확인한다. SDK 차단/오프라인에서도 기존 위치가 삭제되지 않아야 한다.
5. Android Chrome의 작은 화면과 다크 테마, 키보드가 열린 상태에서 입력·검색·저장 접근성과 지도 스크롤을 확인한다.

## 변경 파일

- 모델/서비스/API: `app/Models/LocationRepository.php`, `app/Services/LocationService.php`, `app/Controllers/LocationController.php`, `app/Models/CalendarRepository.php`, `app/Services/CalendarService.php`, `app/Services/TodoService.php`, `app/Controllers/TodoController.php`, `app/Controllers/CalendarController.php`, `public/index.php`
- 공통 UI와 연결: `app/Views/components/location-fields.php`, `public/assets/css/components/location-fields.css`, `public/assets/js/components/location-fields.js`, `app/Views/pages/todo/index.php`, `app/Views/pages/calendar/index.php`, `public/assets/js/pages/todo.js`, `public/assets/js/pages/calendar.js`
- 스키마/환경: `.env.example`, `sql/schema.mysql.sql`, `sql/schema.sqlite.sql`, `sql/migration.saved_locations.mysql.sql`, `sql/migration.saved_locations.sqlite.sql`
- 검증/문서: `tests/saved_locations.php`, 이 문서, `docs/database-migrations.md`, `docs/calendar-feature-implementation.md`, `docs/todo-feature-implementation.md`, `docs/actual-to-plan-conversion-review.md`

수정 PHP 12개 문법 검사, 기존 JS 테스트 31개, 새 컴포넌트 JS 문법 검사 및 공백 검사도 통과했다.

## 실제 메모 위 배치와 계획 위치 조회

실제 일정 추가/수정의 위치 행은 함께 기록하기 안에서 생활 기록 다음, 메모 바로 위에 둔다. 다른 기록 행과 동일하게 가로선으로 구분한다. 계획 블록 수정에도 공유 위치 UI를 읽기 전용으로 추가했다. 계획을 열 때 연결된 daily_plan_item_id의 todo 위치를 조회해 이름/주소/지도/지도 링크를 보여준다. 실제 일정의 별도 위치를 대신 보여주지 않으며, 연결된 할 일이 없으면 행을 숨긴다. 할 일이 있으나 저장 위치가 없으면 안내를 표시한다. 검색/변경/제거는 감추고 location_json 입력을 disabled로 처리해 계획 수정 요청에서 위치를 변경하지 않는다. 위치 편집은 기존 할 일 수정에서 한다.

도메인 등록 후 재검사에서도 현재 .env 키와 Referer http://localhost:8080 조합은 여전히 HTTP 401 domain mismatched를 반환했다. 등록한 앱/JavaScript 키와 현재 키가 같은지 확인이 필요하다. 성공한 지도 호출로 기록하지 않는다.

이번 변경 파일: `app/Views/pages/calendar/index.php`, `public/assets/js/pages/calendar.js`, `app/Views/components/location-fields.php`, `public/assets/js/components/location-fields.js`, `public/assets/css/components/location-fields.css`, 이 문서와 `docs/calendar-feature-implementation.md`.

검증: 수정 PHP 뷰 문법 검사, JS 31개, 공백 검사 통과. SDK mock 브라우저에서 320/390/1024px의 기존 검색 회귀와 계획 위치 조회, 편집 동작 숨김, form 직렬화 제외, 소스 없는 계획의 행 숨김을 확인했다. 수동 확인: 실제 추가/수정에서 위치가 메모 바로 위인지 확인하고, 위치가 등록된 할 일을 계획 배치한 뒤 계획 수정에서 같은 장소/지도를 확인한다. 위치 없는 할 일과 다른 계획을 연속으로 열어 이전 장소가 남지 않는지 확인한다.

## 카카오 활성화 후 실제 검수 완료

도메인 수정 후 401은 해소됐으나 카카오맵 비활성화(403 disabled OPEN_MAP_AND_LOCAL)가 확인됐다. 사용자가 카카오맵 상태를 변경한 후 같은 키·http://localhost:8080 조합의 SDK 응답은 HTTP 200으로 정상화됐다. 실제 Chrome과 실제 SDK로 서울시청을 검색하고 서울특별시청(서울 중구 세종대로 110)을 선택해 좌표 직렬화, 장소 카드, 실제 지도 타일 로드 11개 및 지도 마커를 확인했다. 실제 지도 화면도 시각 검수했다. 오류 메시지는 비어 있었다. 이 결과가 위의 이전 차단 상태 기록보다 최신이다. 검수용 임시 PHP 페이지는 검사 직후 제거했고 사용자 일정은 생성/수정하지 않았다. 이번에는 소스 변경 없이 이 검수 기록만 갱신했다.
