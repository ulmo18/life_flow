(function () {
  let sdkPromise;
  function loadSdk(key) {
    if (window.kakao?.maps?.services) return Promise.resolve(window.kakao.maps);
    if (!key) return Promise.reject(new Error('위치 검색을 사용할 수 없습니다. 지도 설정을 확인해주세요.'));
    if (sdkPromise) return sdkPromise;
    sdkPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      const timer = window.setTimeout(() => fail(), 12000);
      const fail = () => { window.clearTimeout(timer); script.remove(); reject(new Error('지도를 불러오지 못했습니다. 연결 상태와 카카오맵 등록 도메인을 확인해주세요.')); };
      script.src = 'https://dapi.kakao.com/v2/maps/sdk.js?autoload=false&libraries=services&appkey=' + encodeURIComponent(key);
      script.onerror = fail;
      script.onload = () => {
        if (!window.kakao?.maps?.load) return fail();
        window.kakao.maps.load(() => {
          window.clearTimeout(timer);
          if (!window.kakao.maps.services) return fail();
          resolve(window.kakao.maps);
        });
      };
      document.head.append(script);
    }).catch(error => { sdkPromise = null; throw error; });
    return sdkPromise;
  }
  const controllers = new WeakMap();
  document.querySelectorAll('[data-location-fields]').forEach(root => {
    const form = root.closest('form');
    const readOnly = root.hasAttribute('data-location-readonly');
    const el = name => root.querySelector('[data-location-' + name + ']');
    let value = null, generation = 0, queryVersion = 0, map, marker;
    const message = text => { el('message').textContent = text; };
    function draw(next, dirty = false) {
      value = next;
      el('value').value = dirty ? JSON.stringify(next) : '';
      el('summary').textContent = next?.name || '등록 안 함';
      el('card').hidden = !next;
      el('search').hidden = readOnly || Boolean(next);
      el('results').replaceChildren();
      el('query').value = '';
      message('');
      if (readOnly) message(next ? '연결된 할 일의 위치입니다.' : '연결된 할 일에 등록된 위치가 없습니다.');
      if (marker) marker.setMap(null);
      if (next) {
        el('name').textContent = next.name;
        el('address').textContent = next.address;
        el('link').href = 'https://map.kakao.com/link/map/' + encodeURIComponent(next.name) + ',' + next.lat + ',' + next.lng;
        if (root.open) renderMap();
      }
    }
    async function renderMap() {
      const selected = value, version = generation;
      if (!selected || !root.open) return;
      try {
        const maps = await loadSdk(root.dataset.mapKey);
        if (value !== selected || version !== generation || !root.open) return;
        const center = new maps.LatLng(selected.lat, selected.lng);
        if (!map) map = new maps.Map(el('map'), {center, level:3, draggable:false, scrollwheel:false});
        map.relayout();
        map.setCenter(center);
        if (marker) marker.setMap(null);
        marker = new maps.Marker({position:center, map});
      } catch (error) {
        if (value === selected && version === generation) message(error.message);
      }
    }
    async function open(type, id) {
      const version = ++generation;
      const hasOwner = Number.isInteger(Number(id)) && Number(id) > 0;
      root.hidden = readOnly && !hasOwner;
      queryVersion++;
      root.open = false;
      draw(null);
      if (!hasOwner) return;
      el('summary').textContent = '불러오는 중';
      try {
        const response = await fetch('/locations?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id), {headers:{Accept:'application/json'}, cache:'no-store'});
        const result = await response.json();
        if (version !== generation) return;
        if (!response.ok || !result.ok) throw new Error(result.message || '위치를 불러오지 못했습니다.');
        draw(result.location);
      } catch (error) {
        if (version !== generation) return;
        el('summary').textContent = '불러오기 실패';
        message(error.message || '위치를 불러오지 못했습니다.');
      }
    }
    function searchCall(maps, method, term) {
      return new Promise((resolve, reject) => {
        const service = method === 'addressSearch' ? new maps.services.Geocoder() : new maps.services.Places();
        service[method](term, (data, status) => {
          if (status === maps.services.Status.OK) resolve(data);
          else if (status === maps.services.Status.ZERO_RESULT) resolve([]);
          else reject(new Error('검색에 실패했습니다. 잠시 후 다시 시도해주세요.'));
        });
      });
    }
    async function search() {
      if (readOnly) return;
      const term = el('query').value.trim();
      if (!term) { message('주소 또는 장소명을 입력해주세요.'); return; }
      const version = ++queryVersion, ownerVersion = generation;
      el('results').replaceChildren();
      message('검색 중…');
      try {
        const maps = await loadSdk(root.dataset.mapKey);
        const results = await Promise.allSettled([searchCall(maps, 'addressSearch', term), searchCall(maps, 'keywordSearch', term)]);
        if (version !== queryVersion || ownerVersion !== generation) return;
        if (results.every(result => result.status === 'rejected')) throw results[0].reason;
        const seen = new Set();
        const places = results.flatMap(result => result.status === 'fulfilled' ? result.value : []).map(place => ({
          name:place.place_name || place.road_address?.building_name || place.address_name,
          address:place.road_address_name || place.road_address?.address_name || place.address_name,
          lat:Number(place.y), lng:Number(place.x),
        })).filter(place => {
          const key = place.name + '|' + place.lat + '|' + place.lng;
          if (!place.name || !place.address || !Number.isFinite(place.lat) || !Number.isFinite(place.lng) || seen.has(key)) return false;
          seen.add(key); return true;
        }).slice(0,15);
        for (const place of places) {
          const li = document.createElement('li'), button = document.createElement('button'), name = document.createElement('span'), address = document.createElement('small');
          button.type = 'button'; name.textContent = place.name; address.textContent = place.address;
          button.append(name, address); li.append(button); el('results').append(li);
          button.addEventListener('click', () => { generation++; queryVersion++; draw(place, true); });
        }
        message(places.length ? '원하는 위치를 선택해주세요.' : '검색 결과가 없습니다. 주소나 장소명을 다시 입력해주세요.');
      } catch (error) {
        if (version === queryVersion && ownerVersion === generation) message(error.message);
      }
    }
    el('find').addEventListener('click', search);
    el('query').addEventListener('input', () => { queryVersion++; el('results').replaceChildren(); message(''); });
    el('query').addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); event.stopPropagation(); search(); } });
    el('remove').addEventListener('click', () => { generation++; queryVersion++; draw(null, true); el('query').focus(); });
    el('change').addEventListener('click', () => { el('search').hidden = false; el('query').focus(); });
    root.addEventListener('toggle', () => { if (root.open) renderMap(); });
    const observer = new ResizeObserver(() => { if (root.open && value && map) { map.relayout(); map.setCenter(new window.kakao.maps.LatLng(value.lat,value.lng)); } });
    observer.observe(el('map'));
    form.addEventListener('reset', () => { generation++; queryVersion++; queueMicrotask(() => draw(null)); });
    controllers.set(form, {open});
  });
  window.LifeFlowLocation = {open(form, type, id) { return controllers.get(form)?.open(type, id); }};
})();
