import { prefersReducedMotion } from '../utils/env.js';

const tetrominos = [
  // box
  {
    colors: ['rgb(59,84,165)', 'rgb(118,137,196)', 'rgb(79,111,182)'],
    data: [
      [0, 0, 0, 0],
      [0, 1, 1, 0],
      [0, 1, 1, 0],
      [0, 0, 0, 0],
    ],
  },
  // stick
  {
    colors: ['rgb(214,30,60)', 'rgb(241,108,107)', 'rgb(236,42,75)'],
    data: [
      [0, 0, 0, 0],
      [0, 0, 0, 0],
      [1, 1, 1, 1],
      [0, 0, 0, 0],
    ],
  },
  // z
  {
    colors: ['rgb(88,178,71)', 'rgb(150,204,110)', 'rgb(115,191,68)'],
    data: [
      [0, 0, 0, 0],
      [0, 1, 1, 0],
      [0, 0, 1, 1],
      [0, 0, 0, 0],
    ],
  },
  // T
  {
    colors: ['rgb(62,170,212)', 'rgb(120,205,244)', 'rgb(54,192,240)'],
    data: [
      [0, 0, 0, 0],
      [0, 1, 1, 1],
      [0, 0, 1, 0],
      [0, 0, 0, 0],
    ],
  },
  // s
  {
    colors: ['rgb(236,94,36)', 'rgb(234,154,84)', 'rgb(228,126,37)'],
    data: [
      [0, 0, 0, 0],
      [0, 1, 1, 0],
      [1, 1, 0, 0],
      [0, 0, 0, 0],
    ],
  },
  // backwards L
  {
    colors: ['rgb(220,159,39)', 'rgb(246,197,100)', 'rgb(242,181,42)'],
    data: [
      [0, 0, 1, 0],
      [0, 0, 1, 0],
      [0, 1, 1, 0],
      [0, 0, 0, 0],
    ],
  },
  // L
  {
    colors: ['rgb(158,35,126)', 'rgb(193,111,173)', 'rgb(179,63,151)'],
    data: [
      [0, 1, 0, 0],
      [0, 1, 0, 0],
      [0, 1, 1, 0],
      [0, 0, 0, 0],
    ],
  },
];

function randInt(maxExclusive) {
  return Math.floor(Math.random() * maxExclusive);
}

function isCoarsePointer() {
  return typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
}

function getDpr() {
  return Math.max(1, Math.min(2, window.devicePixelRatio || 1));
}

class TetrisGame {
  constructor({ mount, x = 0, y = 0, width, height, unitSize = 20 }) {
    this.mount = mount;
    this.x = x;
    this.y = y;
    this.width = width;
    this.height = height;
    this.unitSize = unitSize;

    this.bgCanvas = document.createElement('canvas');
    this.fgCanvas = document.createElement('canvas');
    this.bgCtx = this.bgCanvas.getContext('2d', { alpha: true });
    this.fgCtx = this.fgCanvas.getContext('2d', { alpha: true });

    for (const c of [this.bgCanvas, this.fgCanvas]) {
      c.className = 'footer-tetris-canvas';
      c.style.position = 'absolute';
      c.style.left = `${this.x}px`;
      c.style.top = `${this.y}px`;
      c.style.width = `${this.width}px`;
      c.style.height = `${this.height}px`;
    }

    mount.appendChild(this.bgCanvas);
    mount.appendChild(this.fgCanvas);

    this.curPiece = { data: null, colors: ['rgb(0,0,0)', 'rgb(0,0,0)', 'rgb(0,0,0)'], x: 0, y: 0 };
    this.curSpeed = 50 + Math.random() * 50;
    this.lastMoveAt = 0;

    this.linesCleared = 0;
    this.level = 0;
    this.loseBlock = 0;

    this._resizeCanvases();
    this.init();
  }

  _resizeCanvases() {
    const dpr = getDpr();
    for (const [canvas, ctx] of [
      [this.bgCanvas, this.bgCtx],
      [this.fgCanvas, this.fgCtx],
    ]) {
      canvas.width = Math.max(1, Math.floor(this.width * dpr));
      canvas.height = Math.max(1, Math.floor(this.height * dpr));
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.imageSmoothingEnabled = false;
    }
  }

  resize({ x = this.x, y = this.y, width = this.width, height = this.height } = {}) {
    this.x = x;
    this.y = y;
    this.width = width;
    this.height = height;

    for (const c of [this.bgCanvas, this.fgCanvas]) {
      c.style.left = `${this.x}px`;
      c.style.top = `${this.y}px`;
      c.style.width = `${this.width}px`;
      c.style.height = `${this.height}px`;
    }

    this._resizeCanvases();
    this.init();
  }

  init() {
    this.curPiece.data = null;
    this.curPiece.colors = ['rgb(0,0,0)', 'rgb(0,0,0)', 'rgb(0,0,0)'];
    this.curPiece.x = 0;
    this.curPiece.y = 0;

    this.lastMoveAt = performance.now();
    this.curSpeed = 55 + Math.random() * 65;
    this.linesCleared = 0;
    this.level = 0;
    this.loseBlock = 0;

    this.boardWidth = Math.max(6, Math.floor(this.width / this.unitSize));
    this.boardHeight = Math.max(6, Math.floor(this.height / this.unitSize));

    this.board = Array.from({ length: this.boardWidth }, () =>
      Array.from({ length: this.boardHeight }, () => ({
        data: 0,
        colors: ['rgb(0,0,0)', 'rgb(0,0,0)', 'rgb(0,0,0)'],
      })),
    );

    // Seed the bottom half with random blocks.
    const halfHeight = Math.floor(this.boardHeight / 2);
    for (let x = 0; x < this.boardWidth; x++) {
      for (let y = 0; y < this.boardHeight; y++) {
        if (Math.random() > 0.15 && y > halfHeight) {
          this.board[x][y] = {
            data: 1,
            colors: tetrominos[randInt(tetrominos.length)].colors,
          };
        }
      }
    }

    // Collapse the board a bit.
    for (let x = 0; x < this.boardWidth; x++) {
      for (let y = this.boardHeight - 1; y >= 0; y--) {
        if (this.board[x][y].data === 0 && y > 0) {
          for (let yy = y; yy > 0; yy--) {
            if (this.board[x][yy - 1].data) {
              this.board[x][yy].data = 1;
              this.board[x][yy].colors = this.board[x][yy - 1].colors;
              this.board[x][yy - 1].data = 0;
              this.board[x][yy - 1].colors = ['rgb(0,0,0)', 'rgb(0,0,0)', 'rgb(0,0,0)'];
            }
          }
        }
      }
    }

    this.checkLines();
    this.renderBoard();
    this.newTetromino();
    this.renderPiece();
  }

  destroy() {
    this.bgCanvas.remove();
    this.fgCanvas.remove();
  }

  step(now) {
    const curPiece = this.curPiece;

    // piece is blocked
    if (!this.checkMovement(curPiece, 0, 1)) {
      if (curPiece.y < -1) {
        // "lose" -> restart for a looping background
        this.init();
        return;
      }

      this.fillBoard(curPiece);
      this.newTetromino();
    } else if (now >= this.lastMoveAt) {
      this.lastMoveAt = now + this.curSpeed;

      if (this.checkMovement(curPiece, 0, 1)) {
        curPiece.y++;
      } else {
        this.fillBoard(curPiece);
        this.newTetromino();
      }
    }

    this.renderPiece();
  }

  renderBoard() {
    const ctx = this.bgCtx;
    ctx.clearRect(0, 0, this.width, this.height);

    for (let x = 0; x < this.boardWidth; x++) {
      for (let y = 0; y < this.boardHeight; y++) {
        const cell = this.board[x][y];
        if (!cell || cell.data === 0) continue;

        const bX = x * this.unitSize;
        const bY = y * this.unitSize;

        ctx.fillStyle = cell.colors[0];
        ctx.fillRect(bX, bY, this.unitSize, this.unitSize);

        ctx.fillStyle = cell.colors[1];
        ctx.fillRect(bX + 2, bY + 2, this.unitSize - 4, this.unitSize - 4);

        ctx.fillStyle = cell.colors[2];
        ctx.fillRect(bX + 4, bY + 4, this.unitSize - 8, this.unitSize - 8);
      }
    }
  }

  renderPiece() {
    const ctx = this.fgCtx;
    const curPiece = this.curPiece;
    if (!curPiece.data) return;

    ctx.clearRect(0, 0, this.width, this.height);

    for (let x = 0; x < 4; x++) {
      for (let y = 0; y < 4; y++) {
        if (curPiece.data[x][y] !== 1) continue;

        const xPos = (curPiece.x + x) * this.unitSize;
        const yPos = (curPiece.y + y) * this.unitSize;
        if (yPos <= -1) continue;

        ctx.fillStyle = curPiece.colors[0];
        ctx.fillRect(xPos, yPos, this.unitSize, this.unitSize);

        ctx.fillStyle = curPiece.colors[1];
        ctx.fillRect(xPos + 2, yPos + 2, this.unitSize - 4, this.unitSize - 4);

        ctx.fillStyle = curPiece.colors[2];
        ctx.fillRect(xPos + 4, yPos + 4, this.unitSize - 8, this.unitSize - 8);
      }
    }
  }

  checkMovement(curPiece, newX, newY) {
    const piece = curPiece.data;
    const posX = curPiece.x;
    const posY = curPiece.y;

    for (let x = 0; x < 4; x++) {
      for (let y = 0; y < 4; y++) {
        if (piece[x][y] !== 1) continue;

        const nextX = posX + x + newX;
        const nextY = posY + y + newY;

        if (nextX < 0 || nextX >= this.boardWidth) return false;
        if (nextY >= this.boardHeight) return false;

        if (nextY >= 0 && this.board[nextX]?.[nextY]?.data === 1) return false;
      }
    }

    return true;
  }

  checkLines() {
    for (let y = this.boardHeight - 1; y >= 0; y--) {
      let filled = 0;
      for (let x = 0; x < this.boardWidth; x++) {
        if (this.board[x][y].data === 1) filled++;
      }

      if (filled === this.boardWidth) {
        this.linesCleared++;
        this.level = Math.round(this.linesCleared / 20) * 20;

        for (let lineY = y; lineY > 0; lineY--) {
          for (let x = 0; x < this.boardWidth; x++) {
            this.board[x][lineY].data = this.board[x][lineY - 1].data;
            this.board[x][lineY].colors = this.board[x][lineY - 1].colors;
          }
        }

        // clear the top line
        for (let x = 0; x < this.boardWidth; x++) {
          this.board[x][0].data = 0;
          this.board[x][0].colors = ['rgb(0,0,0)', 'rgb(0,0,0)', 'rgb(0,0,0)'];
        }

        // re-check this y after collapsing
        y++;
      }
    }
  }

  fillBoard(curPiece) {
    const piece = curPiece.data;
    const posX = curPiece.x;
    const posY = curPiece.y;

    for (let x = 0; x < 4; x++) {
      for (let y = 0; y < 4; y++) {
        if (piece[x][y] !== 1) continue;
        const bx = x + posX;
        const by = y + posY;
        if (by < 0 || bx < 0 || bx >= this.boardWidth || by >= this.boardHeight) continue;
        this.board[bx][by].data = 1;
        this.board[bx][by].colors = curPiece.colors;
      }
    }

    this.checkLines();
    this.renderBoard();
  }

  rotateTetrimono(curPiece) {
    const rotated = Array.from({ length: 4 }, () => Array.from({ length: 4 }, () => 0));
    for (let x = 0; x < 4; x++) {
      for (let y = 0; y < 4; y++) {
        rotated[x][y] = curPiece.data[3 - y][x];
      }
    }

    if (
      !this.checkMovement(
        {
          data: rotated,
          x: curPiece.x,
          y: curPiece.y,
        },
        0,
        0,
      )
    ) {
      return curPiece.data;
    }

    return rotated;
  }

  newTetromino() {
    const pieceNum = randInt(tetrominos.length);
    const curPiece = this.curPiece;
    curPiece.data = tetrominos[pieceNum].data;
    curPiece.colors = tetrominos[pieceNum].colors;
    curPiece.x = Math.floor(Math.random() * (this.boardWidth - curPiece.data.length + 1));
    curPiece.y = -4;

    // occasional rotate for variation
    if (Math.random() > 0.6) curPiece.data = this.rotateTetrimono(curPiece);
  }
}

class FooterTetrisBackground {
  constructor({ footer }) {
    this.footer = footer;
    this.wrap = document.createElement('div');
    this.wrap.className = 'footer-tetris';
    this.wrap.setAttribute('aria-hidden', 'true');

    // Ensure we sit behind content and don't interfere with interaction.
    this.wrap.style.position = 'absolute';
    this.wrap.style.inset = '0';
    this.wrap.style.pointerEvents = 'none';

    footer.prepend(this.wrap);

    this.instances = [];
    this.running = false;
    this.rafId = null;
    this._lastLayoutKey = '';

    this._resizeObserver = new ResizeObserver(() => this.layout());
    this._resizeObserver.observe(footer);

    this._intersectionObserver = new IntersectionObserver(
      (entries) => {
        const isVisible = entries.some((e) => e.isIntersecting);
        if (isVisible) this.start();
        else this.stop();
      },
      { root: null, threshold: 0.01 },
    );
    this._intersectionObserver.observe(footer);

    // Pause in background tabs.
    this._onVisibility = () => {
      if (document.hidden) this.stop();
      else this.start();
    };
    document.addEventListener('visibilitychange', this._onVisibility);

    this.layout();
  }

  destroy() {
    this.stop();
    this._resizeObserver?.disconnect();
    this._intersectionObserver?.disconnect();
    document.removeEventListener('visibilitychange', this._onVisibility);
    for (const inst of this.instances) inst.destroy();
    this.instances = [];
    this.wrap.remove();
  }

  layout() {
    const rect = this.footer.getBoundingClientRect();
    const width = Math.floor(rect.width);
    const height = Math.floor(rect.height);
    if (width <= 0 || height <= 0) return;

    const unitSize = 20;
    const approxBoardWidth = 240;
    const boards = Math.max(1, Math.min(8, Math.round(width / approxBoardWidth)));
    const colWidth = Math.floor(width / boards);

    const key = `${width}x${height}:${boards}:${unitSize}`;
    if (key === this._lastLayoutKey) return;
    this._lastLayoutKey = key;

    for (const inst of this.instances) inst.destroy();
    this.instances = [];
    this.wrap.innerHTML = '';

    for (let i = 0; i < boards; i++) {
      const x = i * colWidth;
      const w = i === boards - 1 ? width - x : colWidth;
      this.instances.push(new TetrisGame({ mount: this.wrap, x, y: 0, width: w, height, unitSize }));
    }
  }

  start() {
    if (this.running) return;
    this.running = true;

    const tick = (now) => {
      if (!this.running) return;
      for (const inst of this.instances) inst.step(now);
      this.rafId = requestAnimationFrame(tick);
    };

    this.rafId = requestAnimationFrame(tick);
  }

  stop() {
    this.running = false;
    if (this.rafId) cancelAnimationFrame(this.rafId);
    this.rafId = null;
  }
}

export function initFooterTetris({ reducedMotion } = {}) {
  const effectiveReducedMotion = typeof reducedMotion === 'boolean' ? reducedMotion : prefersReducedMotion();

  // Follow motion rules: no heavy background animation in reduced-motion mode.
  if (effectiveReducedMotion) return { destroy() {} };
  if (isCoarsePointer()) return { destroy() {} };

  const footer = document.querySelector('[data-footer-tetris]') || document.querySelector('.footer');
  if (!footer) return { destroy() {} };

  return new FooterTetrisBackground({ footer });
}

