/*
 * Ray.Di bindings viewer.
 *
 * Decorates a bindings.md embedded as <pre id="src"> into the mount point
 * <div id="view">: colour-coded provenance events, a type filter, and the
 * discarded side of every collision shown in red. Shared across consumers
 * (the bin/bindings-html CLI, documentation generators) via jsDelivr, so the
 * rendering lives in one place. Without this script the <pre> stays visible as
 * a plain-text fallback.
 */
(function () {
  function run() {
    var src = document.getElementById('src');
    var view = document.getElementById('view');
    if (!src || !view) { return; }
    var text = src.textContent;

    var srcmap = [];
    var mapEl = document.getElementById('srcmap');
    if (mapEl) { try { srcmap = JSON.parse(mapEl.textContent) || []; } catch (e) { srcmap = []; } }

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // esc for a double-quoted attribute value (esc leaves quotes untouched)
    function attr(s) { return esc(s).replace(/"/g, '&quot;'); }

    function resolve(fqcn) {
      var best = null;
      for (var i = 0; i < srcmap.length; i++) {
        var e = srcmap[i];
        if (fqcn.indexOf(e.p) === 0 && (!best || e.p.length > best.p.length)) { best = e; }
      }
      // only link http(s) repositories, so a hostile source URL can't become a javascript: href
      if (!best || !/^https?:\/\//.test(best.u)) { return null; }
      var file = (best.d ? best.d + '/' : '') + fqcn.slice(best.p.length).replace(/\\/g, '/') + '.php';
      var gh = best.u.indexOf('github.com') !== -1 ? best.u + '/blob/' + best.r + '/' + file : best.u;
      return { gh: gh, local: 'vendor/' + best.n + '/' + file };
    }

    // esc, but wrap any resolvable class name in a source link: href to the
    // repository (for humans), data-src to the local vendor/ path (for agents)
    function escLink(s) {
      var out = '', last = 0, re = /[A-Za-z_][A-Za-z0-9_]*(?:\\[A-Za-z0-9_]+)+/g, m;
      while ((m = re.exec(s)) !== null) {
        out += esc(s.slice(last, m.index));
        var r = resolve(m[0]);
        out += r
          ? '<a href="' + attr(r.gh) + '" data-src="' + attr(r.local) + '" target="_blank" rel="noopener">' + esc(m[0]) + '</a>'
          : esc(m[0]);
        last = m.index + m[0].length;
      }
      return out + esc(s.slice(last));
    }

    function section(name) {
      var m = text.match(new RegExp('(?:^|\\n)## ' + name + '\\n([\\s\\S]*?)(?:\\n## |$)'));
      if (!m) { return []; }
      return m[1].split('\n').filter(function (l) { return l.trim() !== ''; });
    }

    var summary = '';
    text.split('\n').slice(0, 8).forEach(function (l) {
      if (l.indexOf('bindings ·') !== -1 && summary === '') { summary = l.trim(); }
    });
    var cm = summary.match(/(\d+) bindings . (\d+) modules . (\d+) replaced . (\d+) discarded/);
    var counts = cm ? [cm[1], cm[2], cm[3], cm[4]] : ['?', '?', '?', '?'];

    function renderEvent(line) {
      var m = line.match(/^(bind|replace|keep|move)\s+(.*)$/);
      if (!m) { return '<div class="ev other"><code>' + esc(line) + '</code></div>'; }
      var typ = m[1], body = m[2], extra = '';
      var dm = body.match(/\s\((discarded|replaced) (.*)\)\s*$/);
      if (dm) {
        var kind = dm[1], inner = dm[2];
        body = body.slice(0, dm.index);
        var im = inner.match(/^(.*) @([^ ]+)$/);
        extra = im
          ? '<span class="lost"><span class="tag">' + kind + '</span> ' + escLink(im[1])
              + ' <span class="at">@</span><span class="mod">' + escLink(im[2]) + '</span></span>'
          : '<span class="lost">' + escLink(inner) + '</span>';
      }
      var bm = body.match(/^(.*?) => (.*) @([^ ]+)$/);
      var core = bm
        ? '<span class="key">' + escLink(bm[1]) + '</span><span class="arrow"> =&gt; </span>'
            + '<span class="tgt">' + escLink(bm[2]) + '</span> <span class="at">@</span>'
            + '<span class="mod">' + escLink(bm[3]) + '</span>'
        : '<code>' + esc(body) + '</code>';
      return '<div class="ev ' + typ + '" data-type="' + typ + '"><span class="t">' + typ + '</span>'
        + core + extra + '</div>';
    }

    function renderBinding(line) {
      var m = line.match(/^(.*?) => (.*)$/);
      return m
        ? '<div class="b"><span class="key">' + escLink(m[1]) + '</span>'
            + '<span class="arrow"> =&gt; </span><span class="tgt">' + escLink(m[2]) + '</span></div>'
        : '<div class="b"><code>' + esc(line) + '</code></div>';
    }

    function renderModule(line) {
      var m = line.match(/^- (.*) \((\d+)\)$/);
      return m
        ? '<div class="ml"><span class="mod">' + escLink(m[1]) + '</span><span class="cnt">' + m[2] + '</span></div>'
        : '<div class="ml">' + esc(line) + '</div>';
    }

    var prov = section('Provenance').map(renderEvent).join('');
    var mods = section('Modules').map(renderModule).join('');
    var binds = section('Bindings').map(renderBinding).join('');

    view.innerHTML =
      '<div class="stats">'
      + '<div class="stat"><div class="n">' + counts[0] + '</div><div class="l">bindings</div></div>'
      + '<div class="stat"><div class="n">' + counts[1] + '</div><div class="l">modules</div></div>'
      + '<div class="stat replace"><div class="n">' + counts[2] + '</div><div class="l">replaced</div></div>'
      + '<div class="stat keep"><div class="n">' + counts[3] + '</div><div class="l">discarded</div></div>'
      + '</div>'
      + '<section><h2>Provenance <span class="hint">— who bound what, who was discarded</span></h2>'
      + '<div class="bar"><span class="lbl">filter</span>'
      + '<span class="chip bind" data-t="bind" data-off="0" role="button" tabindex="0">bind</span>'
      + '<span class="chip replace" data-t="replace" data-off="0" role="button" tabindex="0">replace</span>'
      + '<span class="chip keep" data-t="keep" data-off="0" role="button" tabindex="0">keep</span>'
      + '<span class="chip move" data-t="move" data-off="0" role="button" tabindex="0">move</span></div>'
      + '<div class="log" id="prov">' + prov + '</div></section>'
      + '<section><h2>Modules <span class="hint">— composing modules, by binding count</span></h2>'
      + '<div class="grid">' + mods + '</div></section>'
      + '<section><h2>Bindings <span class="hint">— resolved state</span></h2>'
      + '<div class="log">' + binds + '</div></section>';

    var provEl = document.getElementById('prov');
    view.querySelectorAll('.chip').forEach(function (c) {
      function toggle() {
        var off = c.getAttribute('data-off') === '1';
        c.setAttribute('data-off', off ? '0' : '1');
        provEl.classList.toggle('hide-' + c.getAttribute('data-t'), !off);
      }

      c.addEventListener('click', toggle);
      c.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
      });
    });

    // reveal the decorated view, retire the raw data island
    src.style.display = 'none';
    view.style.display = 'block';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
