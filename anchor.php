<?php
/**
 * anchor.php — FakeGuard AI News Anchor (HACKATHON EDITION)
 * ✅ Angry reaction when FAKE detected
 * ✅ Viral spread danger meter
 * ✅ Always-visible floating mini anchor
 * ✅ Place in includes/ and require_once from any page
 *
 * Call from JS: FakeAnchor.speak({ verdict, title, reason, corrected, sources, confidence })
 */
?>

<!-- ══════════════ MINI FLOATING ANCHOR (always visible) ══════════════ -->
<div id="miniAnchor" onclick="FakeAnchor.show()" title="Open AI News Anchor">
  <svg viewBox="0 0 200 320" width="38" height="55" xmlns="http://www.w3.org/2000/svg">
    <rect x="40" y="190" width="120" height="130" rx="12" fill="#1a1a2e"/>
    <polygon points="80,190 100,220 70,240" fill="#0f0f1e"/>
    <polygon points="120,190 100,220 130,240" fill="#0f0f1e"/>
    <rect x="94" y="190" width="12" height="60" fill="#f0f0f0" rx="2"/>
    <polygon points="97,210 103,210 105,245 100,250 95,245" fill="#e63232"/>
    <ellipse cx="40" cy="200" rx="18" ry="12" fill="#1a1a2e"/>
    <ellipse cx="160" cy="200" rx="18" ry="12" fill="#1a1a2e"/>
    <rect x="88" y="165" width="24" height="32" rx="8" fill="#c8a882"/>
    <ellipse cx="100" cy="148" rx="38" ry="42" fill="#c8a882"/>
    <ellipse cx="100" cy="112" rx="38" ry="18" fill="#2a1a0a"/>
    <rect x="62" y="108" width="76" height="18" fill="#2a1a0a" rx="4"/>
    <ellipse cx="86" cy="148" rx="7" ry="7" fill="#fff"/>
    <ellipse cx="114" cy="148" rx="7" ry="7" fill="#fff"/>
    <circle id="miniPupilL" cx="87" cy="149" r="4" fill="#1a0a00"/>
    <circle id="miniPupilR" cx="115" cy="149" r="4" fill="#1a0a00"/>
    <path id="miniMouth" d="M88 168 Q100 176 112 168" stroke="#7a4a3a" stroke-width="2" fill="none" stroke-linecap="round"/>
    <ellipse cx="100" cy="158" rx="5" ry="4" fill="#b89070"/>
  </svg>
  <div id="miniAnchorBadge" style="display:none">!</div>
  <span class="mini-pulse"></span>
  <!-- Corner speech bubble -->
  <div id="miniSpeechBubble"></div>
</div>

<!-- ══════════════ FULL AI ANCHOR OVERLAY (draggable floating window) ══════════════ -->
<div id="anchorOverlay" aria-hidden="true">

  <!-- Drag Handle -->
  <div id="ancDragHandle">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="anc-drag-dots"><span></span><span></span><span></span></div>
      <span class="anc-live-dot"></span>
      <span style="font-family:'Oswald',sans-serif;font-size:0.58rem;letter-spacing:2px;color:rgba(230,50,50,0.8);">LIVE · AI ANCHOR</span>
    </div>
    <div class="anc-drag-title">☰ DRAG TO MOVE · FAKEGUARD BROADCAST</div>
    <button class="anc-drag-close" onclick="FakeAnchor.hide()" title="Close">✕</button>
  </div>

  <!-- Inner: stage + panel -->
  <div id="ancInner">
    <div class="anc-atmosphere" id="ancAtmosphere"></div>

    <!-- ── STAGE ── -->
    <div class="anc-stage">
      <div class="anc-backdrop">
        <div class="anc-grid"></div>
        <div class="anc-desk-glow" id="ancDeskGlow"></div>
      </div>

      <!-- SVG Anchor Character -->
      <svg id="anchorSVG" class="anc-character" viewBox="0 0 200 320" xmlns="http://www.w3.org/2000/svg">
        <rect id="ancSuit" x="40" y="190" width="120" height="130" rx="12" fill="#1a1a2e"/>
        <polygon points="80,190 100,220 70,240" fill="#0f0f1e"/>
        <polygon points="120,190 100,220 130,240" fill="#0f0f1e"/>
        <rect x="94" y="190" width="12" height="60" fill="#f0f0f0" rx="2"/>
        <polygon id="ancTie" points="97,210 103,210 105,245 100,250 95,245" fill="#e63232"/>
        <ellipse cx="40" cy="200" rx="18" ry="12" fill="#1a1a2e"/>
        <ellipse cx="160" cy="200" rx="18" ry="12" fill="#1a1a2e"/>
        <rect x="88" y="165" width="24" height="32" rx="8" fill="#c8a882"/>
        <ellipse id="ancHead" cx="100" cy="148" rx="38" ry="42" fill="#c8a882"/>
        <ellipse id="ancFlushL" cx="80" cy="155" rx="12" ry="8" fill="#e63232" opacity="0"/>
        <ellipse id="ancFlushR" cx="120" cy="155" rx="12" ry="8" fill="#e63232" opacity="0"/>
        <ellipse id="ancSweat" cx="135" cy="130" rx="4" ry="6" fill="#4fc3f7" opacity="0"/>
        <ellipse cx="100" cy="112" rx="38" ry="18" fill="#2a1a0a"/>
        <rect x="62" y="108" width="76" height="18" fill="#2a1a0a" rx="4"/>
        <path id="ancHairStrand" d="M70 110 Q65 95 72 88" stroke="#2a1a0a" stroke-width="4" fill="none" stroke-linecap="round" opacity="0"/>
        <ellipse cx="63" cy="150" rx="7" ry="9" fill="#c8a882"/>
        <ellipse cx="64" cy="150" rx="4" ry="6" fill="#b89070"/>
        <ellipse cx="137" cy="150" rx="7" ry="9" fill="#c8a882"/>
        <ellipse cx="136" cy="150" rx="4" ry="6" fill="#b89070"/>
        <ellipse id="eyeL" cx="86" cy="148" rx="7" ry="7" fill="#fff"/>
        <ellipse id="eyeR" cx="114" cy="148" rx="7" ry="7" fill="#fff"/>
        <circle id="pupilL" cx="87" cy="149" r="4" fill="#1a0a00"/>
        <circle id="pupilR" cx="115" cy="149" r="4" fill="#1a0a00"/>
        <rect id="blinkL" x="79" y="148" width="14" height="0" rx="7" fill="#c8a882"/>
        <rect id="blinkR" x="107" y="148" width="14" height="0" rx="7" fill="#c8a882"/>
        <path id="browL" d="M79 138 Q86 134 93 137" stroke="#2a1a0a" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        <path id="browR" d="M107 137 Q114 134 121 138" stroke="#2a1a0a" stroke-width="2.5" fill="none" stroke-linecap="round"/>
        <path id="veinL" d="M68 135 Q72 128 76 133" stroke="#e63232" stroke-width="1.5" fill="none" opacity="0"/>
        <path id="veinR" d="M132 135 Q128 128 124 133" stroke="#e63232" stroke-width="1.5" fill="none" opacity="0"/>
        <ellipse cx="100" cy="158" rx="5" ry="4" fill="#b89070"/>
        <path id="ancMouth" d="M88 168 Q100 176 112 168" stroke="#7a4a3a" stroke-width="2" fill="none" stroke-linecap="round"/>
        <text id="ancAngerMark" x="155" y="125" font-family="Oswald,sans-serif" font-size="22" fill="#e63232" font-weight="900" opacity="0">!</text>
        <rect id="ancMicStand" x="148" y="220" width="10" height="28" rx="5" fill="#333"/>
        <ellipse cx="153" cy="218" rx="8" ry="11" fill="#555"/>
        <ellipse cx="153" cy="218" rx="5" ry="8" fill="#888"/>
        <circle cx="137" cy="156" r="4" fill="#222"/>
        <line x1="137" y1="160" x2="137" y2="172" stroke="#333" stroke-width="1.5"/>
        <rect x="50" y="295" width="100" height="18" rx="2" fill="#111" opacity="0.8"/>
        <text x="100" y="308" text-anchor="middle" font-family="Oswald,sans-serif" font-size="7" fill="#e63232" letter-spacing="2">AI · NEWS ANCHOR</text>
        <ellipse id="slamEffect" cx="100" cy="310" rx="0" ry="0" fill="#e63232" opacity="0"/>
      </svg>

      <div class="anc-waves" id="ancWaves">
        <span></span><span></span><span></span><span></span><span></span>
      </div>
      <div class="anc-sparks" id="ancSparks">
        <span></span><span></span><span></span><span></span>
      </div>
    </div><!-- /anc-stage -->

    <!-- ── PANEL ── -->
    <div class="anc-panel">
      <div class="anc-verdict-bar" id="ancVerdictBar">
        <span class="anc-verdict-icon" id="ancVerdictIcon">⚠️</span>
        <span class="anc-verdict-label" id="ancVerdictLabel">CHECKING...</span>
        <span class="anc-confidence" id="ancConfidence"></span>
      </div>
      <div class="anc-article-title" id="ancArticleTitle"></div>
      <div class="anc-viral-meter" id="ancViralMeter">
        <div class="anc-viral-label">
          <span>⚡ VIRAL SPREAD DANGER</span>
          <span id="ancViralPct" class="anc-viral-pct">0%</span>
        </div>
        <div class="anc-viral-track">
          <div class="anc-viral-fill" id="ancViralFill"></div>
          <div class="anc-viral-zones">
            <span>LOW</span><span>MEDIUM</span><span>HIGH</span><span>CRITICAL</span>
          </div>
        </div>
        <div class="anc-viral-est" id="ancViralEst"></div>
      </div>
      <div class="anc-tabs">
        <button class="anc-tab active" onclick="FakeAnchor.tab('explain')">WHY FAKE</button>
        <button class="anc-tab" onclick="FakeAnchor.tab('corrected')">REAL NEWS</button>
        <button class="anc-tab" onclick="FakeAnchor.tab('sources')">SOURCES</button>
      </div>
      <div class="anc-tab-content" id="tabExplain">
        <div class="anc-typewriter" id="ancExplainText"></div>
      </div>
      <div class="anc-tab-content" id="tabCorrected" style="display:none">
        <div class="anc-corrected-box">
          <div class="anc-corrected-label">✓ WHAT'S ACTUALLY TRUE</div>
          <div id="ancCorrectedText" class="anc-typewriter"></div>
        </div>
      </div>
      <div class="anc-tab-content" id="tabSources" style="display:none">
        <div class="anc-sources-label">TRUSTED SOURCES TO CHECK</div>
        <ul class="anc-sources-list" id="ancSourcesList"></ul>
      </div>
      <div class="anc-tts-bar">
        <button class="anc-tts-btn" id="ancPlayBtn" onclick="FakeAnchor.toggleTTS()">
          <span id="ancPlayIcon">▶</span> READ ALOUD
        </button>
        <div class="anc-tts-progress">
          <div class="anc-tts-fill" id="ancTTSFill"></div>
        </div>
        <button class="anc-tts-stop" onclick="FakeAnchor.stopTTS()">■</button>
      </div>
    </div><!-- /anc-panel -->

  </div><!-- /ancInner -->
</div><!-- /anchorOverlay -->


<!-- ══════════════ STYLES ══════════════ -->
<style>
/* ── MINI FLOATING ANCHOR (corner bouncer) ── */
#miniAnchor {
  position: fixed;
  width: 64px;
  height: 80px;
  background: linear-gradient(160deg, #1a1a2e 60%, #2a0a0a);
  border: 1px solid rgba(230,50,50,0.4);
  border-radius: 8px 8px 4px 4px;
  cursor: pointer;
  z-index: 9990;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 24px rgba(230,50,50,0.3);
  overflow: visible;
  transition: box-shadow 0.2s, border-color 0.2s;
}
#miniAnchor:hover {
  box-shadow: 0 8px 32px rgba(230,50,50,0.6);
  border-color: rgba(230,50,50,0.8);
}
#miniAnchorBadge {
  position: absolute;
  top: -6px; right: -6px;
  width: 20px; height: 20px;
  background: var(--accent, #e63232);
  color: #fff;
  border-radius: 50%;
  font-size: 0.7rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: badgePop 0.3s ease;
}
@keyframes badgePop { from{transform:scale(0)} to{transform:scale(1)} }
.mini-pulse {
  position: absolute;
  inset: -4px;
  border: 2px solid rgba(230,50,50,0.4);
  border-radius: 10px;
  animation: miniPulse 2s ease-out infinite;
}
@keyframes miniPulse {
  0%  { transform:scale(1);   opacity:1; }
  100%{ transform:scale(1.3); opacity:0; }
}
#miniAnchor svg { filter: drop-shadow(0 2px 8px rgba(0,0,0,0.8)); }
/* Speech bubble */
#miniSpeechBubble {
  position: absolute;
  top: -42px;
  left: 50%;
  transform: translateX(-50%);
  background: #111;
  border: 1px solid rgba(230,50,50,0.5);
  color: #e63232;
  font-family: 'Oswald', sans-serif;
  font-size: 0.52rem;
  letter-spacing: 1.5px;
  padding: 5px 9px;
  border-radius: 4px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s;
  z-index: 10;
}
#miniSpeechBubble::after {
  content: '';
  position: absolute;
  bottom: -6px; left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: rgba(230,50,50,0.5);
  border-bottom: none;
}
#miniSpeechBubble.show { opacity: 1; }

/* ── OVERLAY — now a draggable floating window ── */
#anchorOverlay {
  position: fixed;
  top: 60px;
  left: 50%;
  transform: translateX(-50%);
  width: 860px;
  max-width: 96vw;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 0;
  background: transparent;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.35s ease;
  font-family: 'Source Sans 3', sans-serif;
  border-radius: 6px;
  overflow: visible;
  /* no backdrop, no blur — page stays fully visible behind */
}
#anchorOverlay.active {
  opacity: 1;
  pointer-events: all;
}

/* Drag handle bar */
#ancDragHandle {
  background: linear-gradient(90deg, #1a0000, #111, #1a0000);
  border: 1px solid rgba(230,50,50,0.45);
  border-bottom: none;
  border-radius: 6px 6px 0 0;
  padding: 7px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: grab;
  user-select: none;
  position: relative;
  z-index: 20;
}
#ancDragHandle:active { cursor: grabbing; }
.anc-drag-dots {
  display: flex;
  gap: 3px;
  align-items: center;
}
.anc-drag-dots span {
  display: block;
  width: 14px;
  height: 3px;
  background: rgba(230,50,50,0.35);
  border-radius: 2px;
}
.anc-drag-title {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 3px;
  color: rgba(230,50,50,0.7);
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
.anc-drag-close {
  background: rgba(230,50,50,0.12);
  border: 1px solid rgba(230,50,50,0.3);
  color: var(--accent, #e63232);
  font-size: 0.85rem;
  width: 28px; height: 28px;
  border-radius: 2px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
  z-index: 10;
}
.anc-drag-close:hover { background: rgba(230,50,50,0.3); }

/* Inner content wrapper */
#ancInner {
  display: flex;
  border: 1px solid rgba(230,50,50,0.3);
  border-top: none;
  border-radius: 0 0 6px 6px;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(0,0,0,0.85), 0 0 0 1px rgba(230,50,50,0.1);
}

/* Atmosphere — inside ancInner */
.anc-atmosphere {
  position: absolute;
  inset: 0;
  background:
    repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,0,0,0.08) 3px,rgba(0,0,0,0.08) 4px),
    radial-gradient(ellipse 80% 60% at 30% 50%, rgba(230,50,50,0.04) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
  transition: background 0.6s ease;
}
.anc-atmosphere.angry {
  background:
    repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(230,50,50,0.06) 3px,rgba(230,50,50,0.06) 4px),
    radial-gradient(ellipse 80% 60% at 30% 50%, rgba(230,50,50,0.18) 0%, transparent 70%);
  animation: angryFlicker 0.15s infinite alternate;
}
@keyframes angryFlicker {
  from { opacity: 1; }
  to   { opacity: 0.85; }
}

.anc-live-dot {
  width: 7px; height: 7px;
  background: var(--accent, #e63232);
  border-radius: 50%;
  animation: blink 0.8s infinite;
  flex-shrink: 0;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.1} }

/* ── STAGE ── */
.anc-stage {
  position: relative;
  width: 280px;
  min-height: 440px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  z-index: 1;
}
.anc-backdrop {
  position: absolute;
  inset: 0;
  overflow: hidden;
  border-right: 1px solid rgba(230,50,50,0.15);
}
.anc-grid {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 160px;
  background:
    linear-gradient(rgba(230,50,50,0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(230,50,50,0.06) 1px, transparent 1px);
  background-size: 30px 30px;
  transform: perspective(300px) rotateX(45deg);
  transform-origin: bottom center;
}
.anc-desk-glow {
  position: absolute;
  bottom: 60px; left: 50%;
  transform: translateX(-50%);
  width: 200px; height: 20px;
  background: radial-gradient(ellipse, rgba(230,50,50,0.3) 0%, transparent 70%);
  filter: blur(8px);
  transition: background 0.5s, width 0.5s;
}
.anc-desk-glow.angry {
  width: 280px;
  background: radial-gradient(ellipse, rgba(230,50,50,0.7) 0%, transparent 70%);
}

/* ── CHARACTER ── */
.anc-character {
  position: relative;
  z-index: 2;
  width: 210px;
  height: auto;
  filter: drop-shadow(0 20px 40px rgba(0,0,0,0.8));
  animation: anchorIdle 4s ease-in-out infinite;
}
@keyframes anchorIdle {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-5px); }
}
.anc-character.speaking {
  animation: anchorTalk 0.3s ease-in-out infinite alternate;
}
@keyframes anchorTalk {
  0%   { transform: translateY(0) rotate(-0.3deg); }
  100% { transform: translateY(-3px) rotate(0.3deg); }
}
.anc-character.angry {
  animation: anchorAngry 0.12s ease-in-out infinite alternate;
}
@keyframes anchorAngry {
  0%   { transform: translateY(0) rotate(-1.5deg) scale(1.02); }
  100% { transform: translateY(-6px) rotate(1.5deg) scale(1.02); }
}

/* Sound waves */
.anc-waves {
  position: absolute;
  bottom: 80px; right: 30px;
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 24px;
  opacity: 0;
  transition: opacity 0.3s;
}
.anc-waves.active { opacity: 1; }
.anc-waves span {
  display: block;
  width: 3px;
  background: var(--accent, #e63232);
  border-radius: 2px;
  animation: waveBar 0.6s ease-in-out infinite alternate;
}
.anc-waves span:nth-child(1){ height:6px;  animation-delay:0s;    }
.anc-waves span:nth-child(2){ height:14px; animation-delay:0.1s;  }
.anc-waves span:nth-child(3){ height:22px; animation-delay:0.05s; }
.anc-waves span:nth-child(4){ height:12px; animation-delay:0.15s; }
.anc-waves span:nth-child(5){ height:7px;  animation-delay:0.08s; }
@keyframes waveBar { to { height: 4px; } }

/* Anger sparks */
.anc-sparks {
  position: absolute;
  top: 60px; left: 20px;
  opacity: 0;
  transition: opacity 0.3s;
}
.anc-sparks.active { opacity: 1; }
.anc-sparks span {
  position: absolute;
  width: 4px; height: 4px;
  background: #e63232;
  border-radius: 50%;
  animation: sparkFly 0.8s ease-out infinite;
}
.anc-sparks span:nth-child(1){ left:0;   top:0;  animation-delay:0s;    --tx:20px; --ty:-30px; }
.anc-sparks span:nth-child(2){ left:10px;top:5px;animation-delay:0.2s; --tx:-15px;--ty:-25px; }
.anc-sparks span:nth-child(3){ left:5px; top:10px;animation-delay:0.1s;--tx:25px; --ty:-20px; }
.anc-sparks span:nth-child(4){ left:15px;top:0;  animation-delay:0.3s; --tx:-20px;--ty:-35px; }
@keyframes sparkFly {
  0%  { transform:translate(0,0) scale(1); opacity:1; }
  100%{ transform:translate(var(--tx),var(--ty)) scale(0); opacity:0; }
}

/* ── PANEL ── */
.anc-panel {
  flex: 1;
  max-width: 560px;
  height: 480px;
  background: rgba(14,14,14,0.98);
  border-left: 3px solid var(--accent, #e63232);
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 1;
  overflow: hidden;
}

/* Verdict bar */
.anc-verdict-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid rgba(230,50,50,0.15);
  background: rgba(0,0,0,0.3);
  transition: background 0.5s;
}
.anc-verdict-icon { font-size: 1.4rem; }
.anc-verdict-label {
  font-family: 'Playfair Display', serif;
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--accent, #e63232);
  letter-spacing: -0.5px;
  flex: 1;
}
.anc-verdict-bar.real  .anc-verdict-label { color: var(--green, #00c853); }
.anc-verdict-bar.misleading .anc-verdict-label { color: var(--yellow, #ffd600); }
.anc-verdict-bar.unverified .anc-verdict-label { color: #777; }
.anc-confidence {
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 2px;
  color: #555;
}

/* Article title */
.anc-article-title {
  padding: 12px 20px;
  font-family: 'Playfair Display', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #aaa;
  border-bottom: 1px solid rgba(42,42,42,0.8);
  line-height: 1.4;
}

/* ── VIRAL METER ── */
.anc-viral-meter {
  padding: 12px 20px;
  border-bottom: 1px solid rgba(42,42,42,0.8);
  display: none;
}
.anc-viral-meter.show { display: block; }
.anc-viral-label {
  display: flex;
  justify-content: space-between;
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 2px;
  color: #777;
  margin-bottom: 8px;
}
.anc-viral-pct {
  font-weight: 700;
  color: var(--accent, #e63232);
  transition: color 0.5s;
}
.anc-viral-track {
  position: relative;
  height: 10px;
  background: rgba(255,255,255,0.05);
  border-radius: 5px;
  overflow: hidden;
  margin-bottom: 6px;
}
.anc-viral-fill {
  height: 100%;
  width: 0%;
  border-radius: 5px;
  background: linear-gradient(90deg, #00c853, #ffd600, #ff6b35, #e63232);
  transition: width 1.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
}
.anc-viral-fill::after {
  content: '';
  position: absolute;
  right: 0; top: 0; bottom: 0;
  width: 4px;
  background: #fff;
  border-radius: 2px;
  box-shadow: 0 0 8px #fff;
  opacity: 0.8;
}
.anc-viral-zones {
  display: flex;
  justify-content: space-between;
  font-family: 'Oswald', sans-serif;
  font-size: 0.5rem;
  letter-spacing: 1px;
  color: #333;
}
.anc-viral-est {
  font-family: 'Oswald', sans-serif;
  font-size: 0.68rem;
  letter-spacing: 1px;
  color: var(--accent, #e63232);
  margin-top: 6px;
  min-height: 16px;
  transition: all 0.3s;
}

/* Tabs */
.anc-tabs {
  display: flex;
  border-bottom: 1px solid rgba(42,42,42,0.8);
}
.anc-tab {
  flex: 1;
  padding: 10px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #555;
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 2px;
  cursor: pointer;
  transition: all 0.2s;
}
.anc-tab.active {
  color: var(--accent, #e63232);
  border-bottom-color: var(--accent, #e63232);
}
.anc-tab:hover { color: #999; }

/* Tab content */
.anc-tab-content {
  flex: 1;
  padding: 18px 20px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #2a2a2a transparent;
  animation: slideInUp 0.3s ease;
}
@keyframes slideInUp {
  from { transform: translateY(14px); opacity: 0; }
  to   { transform: translateY(0);   opacity: 1; }
}

.anc-typewriter {
  font-size: 0.9rem;
  line-height: 1.7;
  color: #ccc;
  min-height: 80px;
}
.anc-corrected-box {
  border: 1px solid rgba(0,200,83,0.3);
  border-left: 3px solid var(--green, #00c853);
  background: rgba(0,200,83,0.04);
  border-radius: 2px;
  padding: 14px;
}
.anc-corrected-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 2px;
  color: var(--green, #00c853);
  margin-bottom: 10px;
}
.anc-sources-label {
  font-family: 'Oswald', sans-serif;
  font-size: 0.6rem;
  letter-spacing: 2px;
  color: var(--blue, #2979ff);
  margin-bottom: 12px;
}
.anc-sources-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.anc-sources-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.83rem;
  color: #bbb;
  padding: 8px 12px;
  background: rgba(41,121,255,0.05);
  border: 1px solid rgba(41,121,255,0.15);
  border-radius: 2px;
  transition: background 0.2s;
}
.anc-sources-list li:hover { background: rgba(41,121,255,0.1); }
.anc-sources-list li::before { content: '⊕'; color: var(--blue, #2979ff); font-size: 0.75rem; }

/* TTS bar */
.anc-tts-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-top: 1px solid rgba(42,42,42,0.8);
  background: rgba(0,0,0,0.2);
}
.anc-tts-btn {
  background: rgba(230,50,50,0.12);
  border: 1px solid rgba(230,50,50,0.3);
  color: var(--accent, #e63232);
  font-family: 'Oswald', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 2px;
  padding: 7px 14px;
  cursor: pointer;
  transition: background 0.2s;
  white-space: nowrap;
}
.anc-tts-btn:hover { background: rgba(230,50,50,0.22); }
.anc-tts-btn.speaking { background: var(--accent, #e63232); color: #fff; }
.anc-tts-progress {
  flex: 1;
  height: 3px;
  background: rgba(255,255,255,0.07);
  border-radius: 2px;
  overflow: hidden;
}
.anc-tts-fill {
  height: 100%;
  width: 0%;
  background: var(--accent, #e63232);
  transition: width 0.3s linear;
}
.anc-tts-stop {
  background: none;
  border: 1px solid rgba(255,255,255,0.1);
  color: #555;
  width: 28px; height: 28px;
  cursor: pointer;
  font-size: 0.7rem;
  transition: all 0.2s;
}
.anc-tts-stop:hover { color: #aaa; border-color: rgba(255,255,255,0.3); }

/* Responsive */
@media (max-width: 700px) {
  #anchorOverlay { width: 98vw; top: 10px; left: 1vw; transform: none; }
  #ancInner { flex-direction: column; }
  .anc-stage { width: 100%; min-height: 200px; }
  .anc-panel { max-width: 100%; width: 100%; height: 320px; }
  .anc-character { width: 140px; }
  #miniAnchor { bottom: 90px; right: 12px; width: 52px; height: 66px; }
}
</style>

<script>
/* ══════════════════════════════════════════
   FakeAnchor — HACKATHON EDITION
   Features: Angry reaction, Viral meter, Global presence
══════════════════════════════════════════ */
var FakeAnchor = (function() {

  var overlay      = document.getElementById('anchorOverlay');
  var svg          = document.getElementById('anchorSVG');
  var waves        = document.getElementById('ancWaves');
  var sparks       = document.getElementById('ancSparks');
  var ttsBtn       = document.getElementById('ancPlayBtn');
  var ttsFill      = document.getElementById('ancTTSFill');
  var atmosphere   = document.getElementById('ancAtmosphere');
  var deskGlow     = document.getElementById('ancDeskGlow');
  var activeTab    = 'explain';
  var currentData  = {};
  var utterance    = null;
  var speaking     = false;
  var blinkInterval = null;
  var mouthAnim    = null;
  var isAngry      = false;

  /* ── BLINK ── */
  function startBlink() {
    clearInterval(blinkInterval);
    blinkInterval = setInterval(function() {
      var bl = document.getElementById('blinkL');
      var br = document.getElementById('blinkR');
      if (!bl) return;
      bl.setAttribute('height', 14); bl.setAttribute('y', 141);
      br.setAttribute('height', 14); br.setAttribute('y', 141);
      setTimeout(function() {
        bl.setAttribute('height', 0); bl.setAttribute('y', 148);
        br.setAttribute('height', 0); br.setAttribute('y', 148);
      }, 100);
    }, Math.random() * 2000 + 2500);
  }

  /* ── MOUTH ── */
  function animateMouth(active) {
    clearInterval(mouthAnim);
    var mouth = document.getElementById('ancMouth');
    if (!mouth) return;
    if (!active) {
      mouth.setAttribute('d', isAngry
        ? 'M88 172 Q100 166 112 172'  // angry frown
        : 'M88 168 Q100 176 112 168');
      return;
    }
    var open = false;
    mouthAnim = setInterval(function() {
      open = !open;
      mouth.setAttribute('d', open
        ? (isAngry ? 'M88 172 Q100 162 112 172' : 'M88 168 Q100 180 112 168')
        : (isAngry ? 'M90 174 Q100 168 110 174' : 'M88 170 Q100 174 112 170'));
    }, isAngry ? 150 : 200);
  }

  /* ── PUPILS ── */
  function lookAround() {
    setInterval(function() {
      var dx = (Math.random() - 0.5) * 4;
      var dy = (Math.random() - 0.5) * 3;
      var pl = document.getElementById('pupilL');
      var pr = document.getElementById('pupilR');
      if (pl) { pl.setAttribute('cx', 87 + dx); pl.setAttribute('cy', 149 + dy); }
      if (pr) { pr.setAttribute('cx', 115 + dx); pr.setAttribute('cy', 149 + dy); }
      // mini anchor pupils too
      var mpl = document.getElementById('miniPupilL');
      var mpr = document.getElementById('miniPupilR');
      if (mpl) { mpl.setAttribute('cx', 87 + dx * 0.5); mpl.setAttribute('cy', 149 + dy * 0.5); }
      if (mpr) { mpr.setAttribute('cx', 115 + dx * 0.5); mpr.setAttribute('cy', 149 + dy * 0.5); }
    }, 1800);
  }

  /* ── ANGRY REACTION ── */
  function triggerAngryReaction() {
    isAngry = true;
    svg.classList.remove('speaking');
    svg.classList.add('angry');
    atmosphere.classList.add('angry');
    deskGlow.classList.add('angry');
    sparks.classList.add('active');

    // Flush cheeks
    animateSVGAttr('ancFlushL', 'opacity', 0, 0.5, 600);
    animateSVGAttr('ancFlushR', 'opacity', 0, 0.5, 600);

    // Angry eyebrows (slant inward)
    var browL = document.getElementById('browL');
    var browR = document.getElementById('browR');
    if (browL) browL.setAttribute('d', 'M79 142 Q86 136 93 140');
    if (browR) browR.setAttribute('d', 'M107 140 Q114 136 121 142');

    // Show veins
    var vL = document.getElementById('veinL');
    var vR = document.getElementById('veinR');
    if (vL) vL.style.opacity = 1;
    if (vR) vR.style.opacity = 1;

    // Messy hair
    var strand = document.getElementById('ancHairStrand');
    if (strand) strand.style.opacity = 1;

    // Anger mark (!)
    var mark = document.getElementById('ancAngerMark');
    if (mark) {
      mark.style.opacity = 1;
      // bounce it
      var bounce = 0;
      var bounceAnim = setInterval(function() {
        bounce++;
        var y = 125 + Math.sin(bounce * 0.8) * 5;
        mark.setAttribute('y', y);
        if (bounce > 20) clearInterval(bounceAnim);
      }, 80);
    }

    // Loosen tie
    var tie = document.getElementById('ancTie');
    if (tie) tie.setAttribute('transform', 'rotate(5, 100, 230)');

    // Screen flash red
    var flash = document.createElement('div');
    flash.style.cssText = 'position:fixed;inset:0;background:rgba(230,50,50,0.15);z-index:9999;pointer-events:none;animation:flashOut 0.4s ease forwards;';
    document.head.insertAdjacentHTML('beforeend', '<style>@keyframes flashOut{to{opacity:0}}</style>');
    document.body.appendChild(flash);
    setTimeout(function(){ flash.remove(); }, 400);

    // Desk slam after 500ms
    setTimeout(function() {
      var slamEl = document.getElementById('slamEffect');
      if (slamEl) {
        var r = 0;
        var slamAnim = setInterval(function() {
          r += 8;
          slamEl.setAttribute('rx', r);
          slamEl.setAttribute('ry', r * 0.3);
          slamEl.style.opacity = Math.max(0, 0.6 - r/80);
          if (r >= 80) { clearInterval(slamAnim); slamEl.style.opacity = 0; }
        }, 20);
      }
    }, 500);

    // Mini anchor reacts too
    var mini = document.getElementById('miniAnchor');
    if (mini) {
      mini.style.background = 'linear-gradient(160deg, #2a0000 60%, #1a0a0a)';
      mini.style.borderColor = 'rgba(230,50,50,0.8)';
      mini.style.boxShadow = '0 4px 24px rgba(230,50,50,0.6)';
    }
    var miniMouth = document.getElementById('miniMouth');
    if (miniMouth) miniMouth.setAttribute('d', 'M88 172 Q100 166 112 172');
  }

  /* ── CALM DOWN ── */
  function calmDown() {
    isAngry = false;
    svg.classList.remove('angry');
    atmosphere.classList.remove('angry');
    deskGlow.classList.remove('angry');
    sparks.classList.remove('active');

    var browL = document.getElementById('browL');
    var browR = document.getElementById('browR');
    if (browL) browL.setAttribute('d', 'M79 138 Q86 134 93 137');
    if (browR) browR.setAttribute('d', 'M107 137 Q114 134 121 138');

    var vL = document.getElementById('veinL');
    var vR = document.getElementById('veinR');
    if (vL) vL.style.opacity = 0;
    if (vR) vR.style.opacity = 0;

    var strand = document.getElementById('ancHairStrand');
    if (strand) strand.style.opacity = 0;

    var mark = document.getElementById('ancAngerMark');
    if (mark) mark.style.opacity = 0;

    var tie = document.getElementById('ancTie');
    if (tie) tie.removeAttribute('transform');

    animateSVGAttr('ancFlushL', 'opacity', 0.5, 0, 1000);
    animateSVGAttr('ancFlushR', 'opacity', 0.5, 0, 1000);

    var mini = document.getElementById('miniAnchor');
    if (mini) {
      mini.style.background = 'linear-gradient(160deg, #1a1a2e 60%, #2a0a0a)';
      mini.style.borderColor = 'rgba(230,50,50,0.4)';
      mini.style.boxShadow = '0 4px 24px rgba(230,50,50,0.3)';
    }
    var miniMouth = document.getElementById('miniMouth');
    if (miniMouth) miniMouth.setAttribute('d', 'M88 168 Q100 176 112 168');
  }

  /* ── VIRAL METER ── */
  function showViralMeter(verdict, confidence) {
    var meter = document.getElementById('ancViralMeter');
    var fill  = document.getElementById('ancViralFill');
    var pct   = document.getElementById('ancViralPct');
    var est   = document.getElementById('ancViralEst');

    if (verdict !== 'fake' && verdict !== 'misleading') {
      meter.classList.remove('show');
      return;
    }
    meter.classList.add('show');

    // Calculate viral score based on verdict + confidence
    var base = verdict === 'fake' ? 60 : 35;
    var score = Math.min(98, base + Math.round(confidence * 0.35));
    var reach = Math.round(score * 42000 + Math.random() * 500000);
    var reachStr = reach > 1000000
      ? (reach/1000000).toFixed(1) + 'M'
      : (reach/1000).toFixed(0) + 'K';

    // Animate fill
    setTimeout(function() {
      fill.style.width = score + '%';
      // Animate percentage counter
      var current = 0;
      var counter = setInterval(function() {
        current += 2;
        if (current >= score) { current = score; clearInterval(counter); }
        pct.textContent = current + '%';
        // Color change
        if (current < 40)      pct.style.color = '#00c853';
        else if (current < 70) pct.style.color = '#ffd600';
        else if (current < 85) pct.style.color = '#ff6b35';
        else                   pct.style.color = '#e63232';
      }, 25);

      // Estimated reach
      setTimeout(function() {
        est.textContent = '⚡ ESTIMATED REACH: ' + reachStr + ' PEOPLE IF SHARED';
      }, 800);
    }, 400);
  }

  /* ── SVG ATTR ANIMATOR ── */
  function animateSVGAttr(id, attr, from, to, duration) {
    var el = document.getElementById(id);
    if (!el) return;
    var start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / duration);
      el.setAttribute(attr, from + (to - from) * p);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* ── VERDICT STYLE ── */
  function applyVerdictStyle(verdict) {
    var bar   = document.getElementById('ancVerdictBar');
    var label = document.getElementById('ancVerdictLabel');
    var icon  = document.getElementById('ancVerdictIcon');
    bar.className = 'anc-verdict-bar ' + verdict;
    label.style.color = '';

    if (verdict === 'fake') {
      label.textContent = '🔴 FAKE NEWS DETECTED';
      icon.textContent  = '🚨';
    } else if (verdict === 'real') {
      label.textContent = '🟢 VERIFIED REAL';
      icon.textContent  = '✅';
      label.style.color = 'var(--green)';
    } else if (verdict === 'misleading') {
      label.textContent = '🟡 MISLEADING CONTENT';
      icon.textContent  = '⚠️';
      label.style.color = 'var(--yellow)';
    } else {
      label.textContent = '⚪ UNVERIFIED';
      icon.textContent  = '🔍';
    }
  }

  /* ── TYPEWRITER ── */
  function typeText(el, text, speed, cb) {
    el.textContent = '';
    var i = 0;
    var timer = setInterval(function() {
      el.textContent += text[i] || '';
      i++;
      if (i >= text.length) { clearInterval(timer); if (cb) cb(); }
    }, speed || 22);
    return timer;
  }

  /* ── POPULATE ── */
  function populate(data) {
    currentData = data;
    applyVerdictStyle(data.verdict || 'unverified');

    var conf = document.getElementById('ancConfidence');
    conf.textContent = data.confidence ? 'CONFIDENCE: ' + data.confidence + '%' : '';

    var titleEl = document.getElementById('ancArticleTitle');
    titleEl.textContent = data.title ? '"' + data.title.substring(0, 90) + '…"' : '';

    var explainEl = document.getElementById('ancExplainText');
    typeText(explainEl, data.reason || 'This article has been flagged by our AI system.', 20);

    var correctedEl = document.getElementById('ancCorrectedText');
    correctedEl.textContent = data.corrected ||
      (data.verdict === 'fake'
        ? 'Based on verified sources, this claim is false. Always cross-reference news with credible outlets before sharing.'
        : 'The article appears to be accurate based on verified sources.');

    var sourcesList = document.getElementById('ancSourcesList');
    sourcesList.innerHTML = '';
    var defaultSources = ['Reuters — reuters.com','BBC News — bbc.com/news','Associated Press — apnews.com','WHO — who.int','PIB Fact Check — pib.gov.in'];
    var sources = (data.sources && data.sources.length) ? data.sources : defaultSources;
    sources.forEach(function(s) {
      var li = document.createElement('li');
      li.textContent = s;
      sourcesList.appendChild(li);
    });

    // Show viral meter
    showViralMeter(data.verdict, data.confidence || 80);
  }

  /* ── TTS ── */
  function buildTTSText() {
    var d = currentData;
    var verdict = d.verdict || 'unverified';
    var verdictWord = verdict === 'fake' ? 'FAKE NEWS' : verdict === 'real' ? 'VERIFIED REAL NEWS' : verdict === 'misleading' ? 'MISLEADING' : 'UNVERIFIED';
    var script = 'Good evening. I am the FakeGuard AI News Anchor. ';
    if (d.title) script += 'The article titled: ' + d.title + '. ';
    script += 'Has been classified as ' + verdictWord + '. ';
    if (d.confidence) script += 'With a confidence of ' + d.confidence + ' percent. ';
    if (verdict === 'fake') script += 'This is extremely dangerous misinformation. ';
    if (d.reason) script += 'Here is why: ' + d.reason + '. ';
    if (d.corrected) script += 'The actual truth is: ' + d.corrected + '. ';
    script += 'Please verify all news before sharing. Stay informed, stay safe.';
    return script;
  }

  function toggleTTS() {
    if (speaking) { stopTTS(); return; }
    if (!window.speechSynthesis) { alert('TTS not supported in this browser.'); return; }
    var text = buildTTSText();
    utterance = new SpeechSynthesisUtterance(text);
    utterance.rate  = 0.92;
    utterance.pitch = currentData.verdict === 'fake' ? 0.85 : 1.0; // deeper voice for fake
    utterance.lang  = 'en-US';

    var voices = window.speechSynthesis.getVoices();
    var preferred = voices.find(function(v) {
      return /david|mark|daniel|google uk|samantha/i.test(v.name);
    });
    if (preferred) utterance.voice = preferred;

    utterance.onstart = function() {
      speaking = true;
      ttsBtn.classList.add('speaking');
      document.getElementById('ancPlayIcon').textContent = '⏸';
      waves.classList.add('active');
      svg.classList.add('speaking');
      animateMouth(true);
    };
    utterance.onboundary = function(e) {
      ttsFill.style.width = Math.min(100, Math.round((e.charIndex / text.length) * 100)) + '%';
    };
    utterance.onend = utterance.onerror = function() { stopTTS(); };
    window.speechSynthesis.speak(utterance);
  }

  function stopTTS() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    speaking = false;
    if (ttsBtn) ttsBtn.classList.remove('speaking');
    var icon = document.getElementById('ancPlayIcon');
    if (icon) icon.textContent = '▶';
    waves.classList.remove('active');
    svg.classList.remove('speaking');
    animateMouth(false);
    if (ttsFill) ttsFill.style.width = '0%';
  }

  /* ── TABS ── */
  function tab(name) {
    activeTab = name;
    ['explain','corrected','sources'].forEach(function(t) {
      var el = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
      if (el) el.style.display = t === name ? 'block' : 'none';
    });
    document.querySelectorAll('.anc-tab').forEach(function(btn, i) {
      btn.classList.toggle('active', ['explain','corrected','sources'][i] === name);
    });
  }

  /* ── DRAG ── */
  function initDrag() {
    var handle = document.getElementById('ancDragHandle');
    var el     = overlay;
    var isDragging = false, startX, startY, startLeft, startTop;

    handle.addEventListener('mousedown', function(e) {
      if (e.target.classList.contains('anc-drag-close')) return;
      isDragging = true;
      // If overlay is using transform (initial center), switch to absolute left/top
      var rect = el.getBoundingClientRect();
      el.style.left      = rect.left + 'px';
      el.style.top       = rect.top  + 'px';
      el.style.transform = 'none';

      startX    = e.clientX;
      startY    = e.clientY;
      startLeft = rect.left;
      startTop  = rect.top;
      handle.style.cursor = 'grabbing';
      e.preventDefault();
    });

    document.addEventListener('mousemove', function(e) {
      if (!isDragging) return;
      var dx = e.clientX - startX;
      var dy = e.clientY - startY;
      var newLeft = Math.max(0, Math.min(window.innerWidth  - el.offsetWidth,  startLeft + dx));
      var newTop  = Math.max(0, Math.min(window.innerHeight - el.offsetHeight, startTop  + dy));
      el.style.left = newLeft + 'px';
      el.style.top  = newTop  + 'px';
    });

    document.addEventListener('mouseup', function() {
      if (isDragging) { isDragging = false; handle.style.cursor = 'grab'; }
    });

    // Touch support
    handle.addEventListener('touchstart', function(e) {
      if (e.target.classList.contains('anc-drag-close')) return;
      var rect = el.getBoundingClientRect();
      el.style.left = rect.left + 'px'; el.style.top = rect.top + 'px'; el.style.transform = 'none';
      startX = e.touches[0].clientX; startY = e.touches[0].clientY;
      startLeft = rect.left; startTop = rect.top;
      isDragging = true;
    }, {passive:true});
    document.addEventListener('touchmove', function(e) {
      if (!isDragging) return;
      var dx = e.touches[0].clientX - startX;
      var dy = e.touches[0].clientY - startY;
      el.style.left = Math.max(0, startLeft + dx) + 'px';
      el.style.top  = Math.max(0, startTop  + dy) + 'px';
    }, {passive:true});
    document.addEventListener('touchend', function() { isDragging = false; });
  }
  initDrag();

  /* ── SHOW / HIDE ── */
  function show() {
    overlay.classList.add('active');
    if (window._anchorPauseWalking) window._anchorPauseWalking(true);
    startBlink();
    lookAround();

    // Trigger angry reaction if fake
    if (currentData && currentData.verdict === 'fake') {
      setTimeout(function() { triggerAngryReaction(); }, 600);
      setTimeout(function() { calmDown(); }, 3500);
    } else {
      calmDown();
    }

    // Auto TTS
    setTimeout(function() {
      if (currentData && currentData.verdict) toggleTTS();
    }, 800);

    // Update mini anchor badge
    var badge = document.getElementById('miniAnchorBadge');
    if (badge) badge.style.display = 'none';
  }

  function hide() {
    overlay.classList.remove('active');
    if (window._anchorPauseWalking) window._anchorPauseWalking(false);
    stopTTS();
    clearInterval(blinkInterval);
    calmDown();
    // Reset position to center for next open
    overlay.style.left      = '50%';
    overlay.style.top       = '60px';
    overlay.style.transform = 'translateX(-50%)';
  }

  /* ── SPEAK (main entry point) ── */
  function speak(data) {
    populate(data);
    tab('explain');
    show();

    // Show badge on mini anchor when new verdict arrives
    var badge = document.getElementById('miniAnchorBadge');
    if (badge) {
      badge.style.display = 'flex';
      badge.textContent = data.verdict === 'fake' ? '🚨' : data.verdict === 'real' ? '✓' : '!';
    }
  }

  // Close on backdrop
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) hide();
  });

  // ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') hide();
  });

  // ── CORNER BOUNCER ──────────────────────────────────────────────
  (function() {
    var el       = document.getElementById('miniAnchor');
    var W        = 64, H = 80;   // anchor size
    var margin   = 14;           // gap from viewport edge
    var speed    = 1.6;          // px per frame (normal)
    var fastMode = false;        // goes fast when FAKE detected
    var paused   = false;        // pause while overlay open

    // Four corners: [x, y]
    var corners = function() {
      return [
        [margin,                          margin],
        [window.innerWidth  - W - margin, margin],
        [window.innerWidth  - W - margin, window.innerHeight - H - margin],
        [margin,                          window.innerHeight - H - margin],
      ];
    };

    var cornerIdx  = 0;   // which corner we're heading to
    var cx = margin;      // current x
    var cy = window.innerHeight - H - margin; // start bottom-left
    var animFrame;

    // Flip the anchor horizontally when moving left
    var facingRight = true;

    function setPos(x, y) {
      el.style.left = x + 'px';
      el.style.top  = y + 'px';
    }
    setPos(cx, cy);

    function nextCorner() {
      cornerIdx = (cornerIdx + 1) % 4;
    }

    function step() {
      if (!paused) {
        var targets = corners();
        var tx = targets[cornerIdx][0];
        var ty = targets[cornerIdx][1];
        var dx = tx - cx;
        var dy = ty - cy;
        var dist = Math.sqrt(dx*dx + dy*dy);
        var spd  = fastMode ? speed * 3.5 : speed;

        if (dist < spd + 1) {
          // Arrived at corner — snap, bounce effect, wait, then go to next
          cx = tx; cy = ty;
          setPos(cx, cy);

          // Squeeze squash animation on arrival
          el.style.transform = 'scaleX(1.3) scaleY(0.75)';
          setTimeout(function() {
            el.style.transform = 'scaleX(0.85) scaleY(1.15)';
            setTimeout(function() {
              el.style.transform = 'scaleX(1) scaleY(1)';
            }, 120);
          }, 80);

          // Update face direction
          nextCorner();
          var nextTargets = corners();
          var nx = nextTargets[cornerIdx][0];
          var goingRight = nx > cx;
          if (goingRight !== facingRight) {
            facingRight = goingRight;
            el.querySelector('svg').style.transform = facingRight ? '' : 'scaleX(-1)';
          }

          // Eyes look toward next corner
          var ml = document.getElementById('miniPupilL');
          var mr = document.getElementById('miniPupilR');
          if (ml) {
            ml.setAttribute('cx', facingRight ? 89 : 85);
            mr.setAttribute('cx', facingRight ? 117 : 113);
          }

          // Corner quip speech bubble
          var quips = fastMode
            ? ['FAKE NEWS!','ALERT!','DANGER!','STOP SHARING!','VERIFY THIS!']
            : ['LIVE CHECK','WATCHING...','FACT CHECK','ALL CLEAR?','MONITORING'];
          var bubble = document.getElementById('miniSpeechBubble');
          if (bubble) {
            bubble.textContent = quips[Math.floor(Math.random() * quips.length)];
            bubble.classList.add('show');
            setTimeout(function() { bubble.classList.remove('show'); }, 900);
          }

          // Short pause at corner
          paused = true;
          setTimeout(function() { paused = false; }, fastMode ? 180 : 600);

        } else {
          // Move toward corner
          cx += (dx / dist) * spd;
          cy += (dy / dist) * spd;
          setPos(cx, cy);

          // Subtle lean in direction of travel
          var lean = fastMode ? (dx > 0 ? 6 : -6) : (dx > 0 ? 2 : -2);
          el.style.transform = 'rotate(' + lean + 'deg)';
        }
      }
      animFrame = requestAnimationFrame(step);
    }
    animFrame = requestAnimationFrame(step);

    // Update corners on resize
    window.addEventListener('resize', function() {
      var c = corners();
      // clamp current pos
      cx = Math.min(cx, window.innerWidth  - W - margin);
      cy = Math.min(cy, window.innerHeight - H - margin);
      setPos(cx, cy);
    });

    // Eyes blink idle on mini anchor
    setInterval(function() {
      var dx = (Math.random() - 0.5) * 3;
      var dy = (Math.random() - 0.5) * 2;
      var ml = document.getElementById('miniPupilL');
      var mr = document.getElementById('miniPupilR');
      if (ml) { ml.setAttribute('cx', 87 + dx); ml.setAttribute('cy', 149 + dy); }
      if (mr) { mr.setAttribute('cx', 115 + dx); mr.setAttribute('cy', 149 + dy); }
    }, 1800);

    // Expose speed control for angry mode
    window._anchorSetFast = function(on) { fastMode = on; };
    window._anchorPauseWalking = function(on) { paused = on; };

  })();

  return { speak: speak, show: show, hide: hide, tab: tab, toggleTTS: toggleTTS, stopTTS: stopTTS };
})();
</script>
