/* Progressive HTML navigation. No client-side policy, REST API or cached document data. */
(() => {
    'use strict';
    const main = document.querySelector('#main');
    if (!main) return;
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const motions = new WeakMap();
    const notice = document.querySelector('#workspace-notice');
    const drawer = document.querySelector('#workspace-drawer');
    const drawerBody = document.querySelector('#workspace-drawer-body');
    const confirmBox = document.querySelector('#workspace-confirm');
    const origin = location.origin;
    const assets = [...document.querySelectorAll('[data-workspace-asset]')].map(n => n.src || n.href).join('|');
    let state = history.state?.iv ? history.state : {iv: true, index: 0, url: location.href, base: null, scroll: scrollY};
    let rendered = location.href, sequence = 0, controller, writing = false, dirty = false, returning = false;
    let opener, confirmResolve, uncertainForm;
    let pendingSelection=[];
    history.replaceState(state, '', location.href);
    history.scrollRestoration = 'manual';
    const absolute = url => new URL(url, location.href).href;
    const path = url => new URL(url, origin).pathname;
    const panelUrl = url => /^\/documents\/(?:create|\d+(?:\/edit)?)\/?$/.test(path(url)) || /^\/admin\/users\/(?:create|\d+\/edit)\/?$/.test(path(url));
    const panelParent = url => path(url).startsWith('/admin/users/') ? '/admin/users' : '/documents';
    const listUrl = url => ['/documents', '/favorites', '/admin/documents', '/admin/confidential-documents'].includes(path(url));
    const downloadable = url => /\/download$|^\/lab\/(?:files|uploads\/\d+)$/.test(path(url));
    const isLocal = url => new URL(url, origin).origin === origin;
    const $ = (selector, root = document) => root.querySelector(selector);

    function rememberScroll() {
        if (drawer.open) return;
        state = {...state, scroll: scrollY};
        history.replaceState(state, '', state.url);
    }
    function commit(url, base, replace = false) {
        state = {iv: true, index: replace ? state.index : state.index + 1, url, base, scroll: base ? base.scroll : scrollY};
        history[replace ? 'replaceState' : 'pushState'](state, '', url);
    }
    function message(text, error = false, recovery) {
        if (drawer.open) drawer.insertBefore(notice, drawerBody);
        else document.body.append(notice);
        notice.replaceChildren();
        notice.className = 'workspace-notice alert ' + (error ? 'alert-danger' : 'alert-success');
        notice.setAttribute('role', error ? 'alert' : 'status');
        notice.append(document.createTextNode(text));
        if (recovery) {
            const check = document.createElement('button');
            check.className = 'btn btn-sm btn-light'; check.textContent = '서버 상태 확인';
            check.addEventListener('click', recovery, {once: true}); notice.append(check);
        }
        const dismiss = document.createElement('button');
        dismiss.className = 'btn btn-sm btn-light'; dismiss.textContent = '알림 닫기';
        dismiss.onclick = () => notice.replaceChildren(); notice.append(dismiss);
    }
    function confirmAction(text, label = '계속') {
        if (confirmResolve) return Promise.resolve(false);
        $('#workspace-confirm-text').textContent = text;
        $('#workspace-confirm-accept').textContent = label;
        confirmBox.showModal(); $('#workspace-confirm-cancel').focus();
        return new Promise(resolve => { confirmResolve = resolve; });
    }
    function finishConfirm(value) { confirmBox.close(); const resolve = confirmResolve; confirmResolve = null; resolve?.(value); }
    $('#workspace-confirm-cancel').onclick = () => finishConfirm(false);
    $('#workspace-confirm-accept').onclick = () => finishConfirm(true);
    confirmBox.addEventListener('cancel', e => { e.preventDefault(); finishConfirm(false); });
    async function canLeave() {
        if (writing) { message('현재 작업이 끝난 뒤 이동해 주세요.', true); return false; }
        if (!dirty) return true;
        const accepted = await confirmAction('저장하지 않은 입력이 있습니다. 입력을 버리고 이동할까요?', '입력 버리고 이동');
        if (accepted) dirty = false;
        return accepted;
    }
    function restoreSelection() {
        for(const [node,wasActive] of pendingSelection) if(node.isConnected) {node.classList.toggle('active',wasActive);node.removeAttribute('data-selection-pending');}
        pendingSelection=[];
    }
    function selectImmediately(url) {
        restoreSelection();
        // Only the selection moves immediately; content and aria-current remain server-confirmed.
        for(const selector of ['.workspace-tabs .workspace-tab','.department-grid .department-card','.sidebar .nav-item']) {
            const links=[...document.querySelectorAll(selector)], target=links.find(n=>n.href===url);
            if(!target) continue;
            pendingSelection.push(...links.map(n=>[n,n.classList.contains('active')]));
            for(const node of links) node.classList.toggle('active',node===target);
            target.setAttribute('data-selection-pending','true');
        }
    }
    function beginRead() {
        controller?.abort(); controller = new AbortController(); main.setAttribute('aria-busy','true');
        return {id: ++sequence, signal: controller.signal};
    }
    function active(task) { return task.id === sequence; }
    async function html(url, task) {
        const group=main.dataset.workspaceGroup || '';
        const sameView=path(url)===path(rendered) || [...main.querySelectorAll('.workspace-tab')].some(n=>n.href===absolute(url).split('?')[0]);
        const fragment=panelUrl(url) ? 'panel' : (group && sameView ? 'view' : '');
        const response = await fetch(url, {signal: task.signal, credentials: 'same-origin', cache: 'no-store', headers: {'X-IntraVault': 'workspace', 'X-IntraVault-Fragment':fragment, 'X-IntraVault-Group':group, 'Accept': 'application/json, text/html'}});
        const text = await response.text();
        if (!active(task)) throw new DOMException('Superseded', 'AbortError');
        if (path(response.url) === '/login' || response.status === 401) { writing=false;dirty=false;location.assign('/login'); throw new DOMException('Login', 'AbortError'); }
        const doc = new DOMParser().parseFromString(text, 'text/html');
        if (!response.ok) {
            let validation; try { validation = JSON.parse(text).errors; } catch {}
            throw Object.assign(new Error(validation ? Object.values(validation).flat().join('\n') : ({403:'이 작업에 접근할 권한이 없습니다.',404:'자료가 없거나 더 이상 접근할 수 없습니다.',419:'세션이 만료되었습니다. 다시 로그인해 주세요.'})[response.status] || '요청을 처리하지 못했습니다.'), {status:response.status});
        }
        if (!$('#main', doc)) throw new Error('화면 응답을 확인하지 못했습니다. 다시 시도해 주세요.');
        // A new deployment must update the script and styles together, never mix versions.
        const nextAssets = [...doc.querySelectorAll('[data-workspace-asset]')].map(n => n.src || n.href).join('|');
        if (nextAssets !== assets) { writing=false;dirty=false;location.assign(response.url); throw new DOMException('Deployment changed', 'AbortError'); }
        return {doc, url: response.url};
    }
    function activateScripts(root) {
        // Blade already decides escaping under S11/V07. Preserve normal server-rendered script semantics.
        for (const old of root.querySelectorAll('script')) {
            const script = document.createElement('script');
            for (const attr of old.attributes) script.setAttribute(attr.name, attr.value);
            script.textContent = old.textContent; old.replaceWith(script);
        }
    }
    function animateChange(target) {
        motions.get(target)?.cancel();
        if (reducedMotion.matches || !target.animate || !target.closest('#main, #workspace-drawer')) return;
        const motion = target.animate([{opacity:.65,transform:'translateY(6px)'},{opacity:1,transform:'translateY(0)'}], {duration:180,easing:'cubic-bezier(.2,.7,.2,1)'});
        motions.set(target,motion);
    }
    function replaceContents(target, source) {
        target.replaceChildren(...[...source.childNodes].map(n => document.importNode(n, true)));
        activateScripts(target); animateChange(target);
    }
    function chrome(doc) {
        for (const selector of ['.sidebar nav', '.sidebar > .nav-label', '.sidebar-bottom', '.breadcrumb-text', '.account']) {
            const current = $(selector), next = $(selector, doc);
            if (current && next && current.innerHTML !== next.innerHTML) replaceContents(current, next);
        }
        const back = $('[data-back-button]'), nextBack = $('[data-back-button]', doc);
        if (back && nextBack) back.href = nextBack.href;
    }
    function syncSearch(root, url) {
        const params = new URL(url).searchParams;
        for (const form of root.querySelectorAll('form[data-workspace-search]')) {
            for (const el of form.elements) if (el.name) el.value = params.get(el.name) || '';
        }
    }
    function renderMain(result, preserve = false) {
        const next = $('#main', result.doc);
        const same = path(rendered) === path(result.url) || (listUrl(rendered) && listUrl(result.url) && main.dataset.workspaceGroup===next.dataset.workspaceGroup);
        const regions = [...next.querySelectorAll('[data-workspace-region]')];
        if (same && preserve && regions.length > 1 && regions.every(n => main.querySelector(`[data-workspace-region="${n.dataset.workspaceRegion}"]`))) {
            for (const source of regions) replaceContents(main.querySelector(`[data-workspace-region="${source.dataset.workspaceRegion}"]`), source);
            syncSearch(main, result.url);
            const heading=$('#workspace-view > .page-heading'), nextHeading=$('#workspace-view > .page-heading',next);
            if(heading&&nextHeading&&heading.innerHTML!==nextHeading.innerHTML) replaceContents(heading,nextHeading);
            const tools = main.querySelector('.practice-tools');
            if (tools) tools.open = !!result.doc.querySelector('.practice-tools[open]');
        } else if (main.dataset.workspaceGroup && main.dataset.workspaceGroup === next.dataset.workspaceGroup && $('#workspace-view') && $('#workspace-view', next)) {
            const view = $('#workspace-view');
            replaceContents(view, $('#workspace-view', next));
            view.setAttribute('aria-label', $('#workspace-view', next).getAttribute('aria-label'));
        } else {
            replaceContents(main, next); main.dataset.workspaceGroup = next.dataset.workspaceGroup || '';
        }
        const currentView=$('#workspace-view'), nextView=$('#workspace-view',next);
        if(currentView&&nextView) currentView.setAttribute('aria-label',nextView.getAttribute('aria-label'));
        for (const tab of main.querySelectorAll('.workspace-tab')) {
            const selected = [...next.querySelectorAll('.workspace-tab')].find(n=>n.href===tab.href)?.hasAttribute('aria-current');
            tab.classList.toggle('active',!!selected);
            if(selected) tab.setAttribute('aria-current','page'); else tab.removeAttribute('aria-current');
        }
        chrome(result.doc); document.title = result.doc.title; rendered = result.url;
    }
    function showPanel(result) {
        const source = $('#workspace-view', result.doc) || $('#main', result.doc);
        drawerBody.className = 'page-content'; replaceContents(drawerBody, source);
        document.title = result.doc.title;
        $('#workspace-drawer-title').textContent = $('h1,h2', drawerBody)?.textContent || '문서';
        if (!drawer.open) { opener = document.activeElement; drawer.showModal(); }
        $('#workspace-drawer-close').focus();
    }
    function hidePanel() {
        document.body.append(notice);
        if (drawer.open) drawer.close(); drawerBody.replaceChildren();
        if (opener?.isConnected) opener.focus({preventScroll:true});
    }
    function readFailure(error, target, task) {
        if (error.name === 'AbortError' || !active(task)) return;
        if ([403,404,419].includes(error.status)) {
            // Never leave a stale authorized document visible after a denied revalidation.
            const root = target === 'panel' ? drawerBody : main;
            root.replaceChildren();
            const p = document.createElement('p'); p.className = 'alert alert-danger'; p.textContent = error.message; root.append(p);
        }
        message(error.message, true);
    }
    async function navigate(url, {replace=false, restore=null, preserve=false, panel=panelUrl(url), checked=false} = {}) {
        if (!checked && !(await canLeave())) return;
        url = absolute(url); if (!isLocal(url)) return;
        if (!restore) rememberScroll(); const task = beginRead(); selectImmediately(url);
        const closing=drawer.open && !panel, returnHref=opener?.closest('a')?.href;
        if(closing) hidePanel();
        let base = restore ? restore.base : (panel ? (state.base || {url:rendered, scroll:scrollY, index:state.index}) : null);
        if (panel && !base) base = {url:absolute(panelParent(url)),scroll:0,index:state.index};
        try {
            if (restore?.base && rendered !== restore.base.url) renderMain(await html(restore.base.url, task), true);
            const result = await html(url, task);
            restoreSelection();
            if (panel) showPanel(result);
            else { hidePanel(); renderMain(result, preserve); }
            if (restore) { state = {...restore,url:result.url}; history.replaceState(state,'',result.url); }
            else commit(result.url, panel ? base : null, replace);
            notice.replaceChildren();
            dirty = false;
            if (!panel) {
                window.scrollTo({top:restore ? restore.scroll : preserve ? state.scroll : 0,behavior:'instant'});
                if (!preserve) { const view=$('#workspace-view') || main; view.tabIndex=-1; view.focus({preventScroll:true}); }
                else if (closing && returnHref) [...main.querySelectorAll('a[href]')].find(a => a.href === returnHref)?.focus({preventScroll:true});
            }
            return true;
        } catch (error) { readFailure(error, panel ? 'panel' : 'main', task); return false; }
        finally { if (active(task)) {restoreSelection();main.removeAttribute('aria-busy');} }
    }
    async function closePanel() {
        if (!(await canLeave())) return;
        if (state.base && state.index > state.base.index) history.go(state.base.index - state.index);
        else await navigate(state.base?.url || panelParent(state.url), {replace:true,checked:true,panel:false,preserve:true});
    }
    $('#workspace-drawer-close').onclick = closePanel;
    drawer.addEventListener('cancel', e => { e.preventDefault(); closePanel(); });
    drawer.addEventListener('click', e => { if (e.target === drawer && e.clientX < drawer.getBoundingClientRect().left) closePanel(); });

    document.addEventListener('click', async event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
        if (!link.closest('.app-shell, #workspace-drawer') || !isLocal(link.href) || downloadable(link.href) || path(link.href) === '/login' || link.getAttribute('href').startsWith('#')) return;
        event.preventDefault();
        if (drawer.open && !panelUrl(link.href) && path(link.href)===path(state.base?.url || panelParent(state.url))) { await closePanel(); return; }
        if (link.hasAttribute('data-workspace-close')) { await closePanel(); return; }
        if (link.hasAttribute('data-back-button') && state.index > 0) { if (await canLeave()) history.back(); return; }
        const preserve = !!link.closest('.department-browser, .pagination') || (listUrl(link.href) && listUrl(rendered) && !drawer.open) || (listUrl(link.href) && path(rendered) === path(link.href));
        await navigate(link.href, {preserve});
    });
    window.addEventListener('popstate', async event => {
        if (returning) { returning = false; return; }
        const target = event.state;
        if (!target?.iv) { location.reload(); return; }
        if (writing || (dirty && !window.confirm('저장하지 않은 입력이 있습니다. 입력을 버리고 이동할까요?'))) {
            returning = true; history.go(state.index - target.index); return;
        }
        dirty = false;
        await navigate(target.url, {restore:target,panel:!!target.base,preserve:true,checked:true});
    });
    window.addEventListener('beforeunload', event => { if (dirty || writing) { event.preventDefault(); event.returnValue = ''; } });
    document.addEventListener('input', event => { if (event.target.closest('[data-workspace-edit]')) dirty = true; });
    document.addEventListener('change', event => { if (event.target.closest('[data-workspace-edit]')) dirty = true; });

    function setBusy(form, value) {
        form.setAttribute('aria-busy', String(value));
        for (const button of form.querySelectorAll('button[type=submit], button:not([type]), input[type=submit]')) {
            if (value) { button.dataset.wasDisabled = String(button.disabled); button.disabled = true; }
            else button.disabled = button.dataset.wasDisabled === 'true';
        }
    }
    function feedback(form, text, error=false) {
        let node = $('.workspace-feedback', form);
        if (!node) { node = document.createElement('div'); node.className = 'workspace-feedback'; form.append(node); }
        node.setAttribute('role',error ? 'alert':'status'); node.textContent = text;
    }
    function send(form, data) {
        return new Promise((resolve,reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(form.method.toUpperCase(), form.action); xhr.timeout = 120000;
            xhr.setRequestHeader('X-IntraVault','workspace'); xhr.setRequestHeader('Accept','application/json');
            xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
            xhr.onload = () => resolve(xhr);
            xhr.onerror = xhr.ontimeout = () => reject(new Error('요청 결과를 받지 못했습니다. 서버에 저장되었을 수 있습니다. 재전송 전에 서버 상태를 확인해 주세요.'));
            xhr.send(data);
        });
    }
    async function refreshAfterWrite(action, resultUrl) {
        const task = beginRead();
        try {
            const isDelete = /\/documents\/\d+$/.test(path(action)) && !panelUrl(resultUrl);
            const completedUser = /^\/admin\/users(?:\/\d+)?$/.test(path(action));
            const target = state.base && !isDelete ? resultUrl : (path(action)==='/lab/uploads' ? state.url : resultUrl);
            if (state.base) {
                const backgroundUrl = isDelete && panelUrl(state.base.url) ? absolute('/documents') : state.base.url;
                const lightweight=path(action).endsWith('/favorite');
                const needsBackground=!lightweight || ['/','/favorites','/my-access-logs','/admin','/admin/audit-logs','/admin/permissions'].includes(path(backgroundUrl));
                const [background,detail] = await Promise.all([
                    needsBackground ? html(backgroundUrl,task) : Promise.resolve(null),
                    isDelete || completedUser ? Promise.resolve(null) : html(target,task)
                ]);
                if(background) {renderMain(background, true); state.base.url = background.url;}
                if (isDelete || completedUser) {
                    hidePanel(); commit(background.url,null,true); window.scrollTo({top:state.scroll,behavior:'instant'});
                } else {
                    if (/\/(favorite|shares(?:\/\d+)?)$/.test(path(action))) {
                        document.title = detail.doc.title;
                        const region = path(action).endsWith('/favorite') ? 'favorite' : 'shares';
                        for (const name of [region, 'status']) {
                            const old = drawerBody.querySelector(`[data-workspace-region="${name}"]`);
                            const next = detail.doc.querySelector(`[data-workspace-region="${name}"]`);
                            if (old && next) replaceContents(old, next);
                        }
                    } else showPanel(detail);
                    commit(detail.url,state.base,true);
                }
            } else {
                const result = await html(target,task);
                const preserve = !(/^\/documents\/\d+$/.test(path(action)) && panelUrl(resultUrl));
                renderMain(result,preserve); commit(result.url,null,true);
            }
        } catch (error) {
            readFailure(error, state.base ? 'panel' : 'main', task);
            throw error;
        } finally { if (active(task)) main.removeAttribute('aria-busy'); }
    }
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('.app-shell, #workspace-drawer') || !isLocal(form.action) || path(form.action)==='/logout' || downloadable(form.action)) return;
        event.preventDefault();
        if (form.method.toLowerCase()==='get') {
            const url=new URL(form.getAttribute('action') || (state.base && form.closest('#main') ? state.base.url : state.url),origin);
            url.search=new URLSearchParams(new FormData(form));
            await navigate(url.href,{preserve:true,panel:false});return;
        }
        if (writing || uncertainForm === form) return;
        if (form.dataset.confirm && !(await confirmAction(form.dataset.confirm,'삭제'))) return;
        if (writing) return;
        const data = new FormData(form);
        if (event.submitter?.name) data.append(event.submitter.name,event.submitter.value);
        writing=true; controller?.abort(); sequence++; restoreSelection(); main.setAttribute('aria-busy','true'); setBusy(form,true); $('.workspace-feedback',form)?.remove();
        let acknowledged=false;
        try {
            const xhr=await send(form,data);
            if (xhr.status===401 || path(xhr.responseURL || form.action)==='/login') { dirty=false;writing=false;location.assign('/login');return; }
            if (xhr.status>=400) {
                let body;try {body=JSON.parse(xhr.responseText);}catch{}
                const text=body?.errors ? Object.values(body.errors).flat().join('\n') : ({403:'이 작업에 접근할 권한이 없습니다.',419:'세션이 만료되었습니다. 다시 로그인해 주세요.',413:'파일 크기가 허용 범위를 초과했습니다.'})[xhr.status] || '작업을 완료하지 못했습니다.';
                feedback(form,text,true);
                if ([403,419].includes(xhr.status)) {
                    await navigate(state.url,{replace:true,checked:true,panel:!!state.base,preserve:false});
                    message(text,true);
                }
                return;
            }
            const resultUrl=xhr.getResponseHeader('X-IntraVault-Location');
            if (xhr.status!==204 || !resultUrl || !isLocal(resultUrl)) throw new Error('서버 처리 결과를 확인하지 못했습니다. 재전송 전에 서버 상태를 확인해 주세요.');
            acknowledged=true; dirty=false;
            if (path(form.action)==='/lab/uploads') form.reset();
            await refreshAfterWrite(form.action,resultUrl);
            message('변경사항을 반영했습니다.');
        } catch(error) {
            uncertainForm=form; setBusy(form,true);
            const text=acknowledged?'저장은 완료됐지만 화면을 갱신하지 못했습니다. 서버 상태를 확인해 주세요.':error.message;
            feedback(form,text,true);
            const actionPath=path(form.action);
            let recoveryUrl=state.url;
            if (actionPath==='/documents' || (actionPath.match(/^\/documents\/\d+$/) && data.get('_method')==='DELETE')) {
                // A prior department/search filter may hide the newly saved record.
                recoveryUrl=absolute('/documents');
                if (data.get('title')) recoveryUrl+='?q='+encodeURIComponent(String(data.get('title')).slice(0,200));
            } else if (actionPath==='/lab/uploads') recoveryUrl=absolute('/lab/search');
            async function checkState() {
                dirty=false;
                const ok=await navigate(recoveryUrl,{replace:true,panel:false,preserve:true,checked:true});
                if(ok) { uncertainForm=null; message('서버 상태를 다시 읽었습니다. 표시된 내용을 확인한 뒤 다음 작업을 진행해 주세요.'); }
                else message('서버 상태를 아직 확인하지 못했습니다. 연결을 확인한 뒤 다시 조회해 주세요.',true,checkState);
            }
            message(text,true,checkState);
        } finally {
            writing=false;main.removeAttribute('aria-busy');
            if (uncertainForm!==form) setBusy(form,false);
        }
    });
    // Direct detail/edit links hydrate into the same hub, retaining a native SSR fallback.
    if (panelUrl(location.href)) {
        const initialUrl=location.href, initial={doc:document.cloneNode(true),url:initialUrl};
        const task=beginRead();
        html(absolute(panelParent(initialUrl)),task).then(result=>{
            if(!active(task) || dirty || writing || location.href!==initialUrl) return;
            renderMain(result); showPanel(initial);
            commit(initialUrl,{url:result.url,scroll:0,index:state.index},true);
        }).catch(error=>readFailure(error,'main',task)).finally(()=>{if(active(task)) main.removeAttribute('aria-busy');});
    }
    // Revalidate restored BFCache pages; session/policy decisions always come from a new request.
    window.addEventListener('pageshow', e => { if(e.persisted) location.reload(); });
})();
