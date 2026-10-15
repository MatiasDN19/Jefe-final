/**
 * La escena 3D de la pantalla grande, hecha con Three.js.
 *
 * Idea general:
 *  - Cada píxel del sprite (jefe-sprite.js) es un cubito 3D.
 *  - Renderizamos a BAJA resolución y estiramos la imagen sin suavizar
 *    (image-rendering: pixelated). Ese truco hace que el 3D se vea pixel art.
 *  - pantalla.blade.php llama a window.Jefe3D.golpe(), .morir(), etc.
 *  - La misma escena se reutiliza en la intro de la portada:
 *    crearEscena(contenedor, { intro: true }) arranca con el jefe
 *    desarmado y la cámara pegada al ojo.
 *    cuando le llegan los eventos de Reverb.
 */
import * as THREE from 'three';
import { FILAS, PALETA, BRILLANTES, ANCHO, ALTO } from './jefe-sprite';

const contenedorPantalla = document.getElementById('escena');
if (contenedorPantalla) window.Jefe3D = crearEscena(contenedorPantalla);

export function crearEscena(contenedor, opciones = {}) {
    const esIntro = Boolean(opciones.intro);
    let detenida = false;
    // =====================================================================
    // 1. Renderer, cámara y luces
    // =====================================================================

    const PIXEL = 3; // 1 píxel del render = 3x3 píxeles en pantalla

    const renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true });
    renderer.setPixelRatio(1);
    const lienzo = renderer.domElement;
    lienzo.style.cssText = 'width:100%;height:100%;display:block;image-rendering:pixelated;';
    contenedor.appendChild(lienzo);

    const escena = new THREE.Scene();
    const camara = new THREE.PerspectiveCamera(38, 1, 0.1, 500);
    camara.position.set(0, -6, 64);

    escena.add(new THREE.AmbientLight(0x7a5cd6, 1.4));
    const luzFrente = new THREE.DirectionalLight(0xffffff, 2);
    luzFrente.position.set(-15, 20, 30);
    escena.add(luzFrente);
    const luzNucleo = new THREE.PointLight(0x5ff3ff, 30, 25, 1);
    luzNucleo.position.set(0, 4, 4);
    escena.add(luzNucleo);
    // Luz extra que se prende un instante con cada rayo
    const luzRayo = new THREE.PointLight(0xffffff, 0, 60, 1);
    escena.add(luzRayo);

    function ajustarTamanio() {
        const w = contenedor.clientWidth || 1;
        const h = contenedor.clientHeight || 1;
        renderer.setSize(Math.ceil(w / PIXEL), Math.ceil(h / PIXEL), false);
        camara.aspect = w / h;
        camara.updateProjectionMatrix();
    }
    new ResizeObserver(ajustarTamanio).observe(contenedor);
    ajustarTamanio();

    // Texturas de degradé dibujadas con un canvas 2D (no hace falta ninguna imagen)
    function texturaRadial() {
        const c = document.createElement('canvas');
        c.width = c.height = 64;
        const g = c.getContext('2d').createRadialGradient(32, 32, 0, 32, 32, 32);
        g.addColorStop(0, 'rgba(255,255,255,1)');
        g.addColorStop(0.4, 'rgba(255,255,255,.35)');
        g.addColorStop(1, 'rgba(255,255,255,0)');
        const ctx = c.getContext('2d');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, 64, 64);
        return new THREE.CanvasTexture(c);
    }
    function texturaVertical() {
        const c = document.createElement('canvas');
        c.width = 4; c.height = 128;
        const ctx = c.getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, 128);
        g.addColorStop(0, 'rgba(255,255,255,0)');
        g.addColorStop(0.3, 'rgba(255,255,255,.9)');
        g.addColorStop(1, 'rgba(255,255,255,0)');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, 4, 128);
        return new THREE.CanvasTexture(c);
    }
    const TEX_RADIAL = texturaRadial();
    const TEX_VERTICAL = texturaVertical();

    // Blending aditivo: los colores se SUMAN a lo que hay atrás, como la luz real
    const luminoso = (color, opacidad, mapa) => new THREE.MeshBasicMaterial({
        color, map: mapa, transparent: true, opacity: opacidad,
        blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide,
    });

    // =====================================================================
    // 2. El fondo: haces de luz, aura y círculo mágico
    // =====================================================================

    // Haces de luz que bajan desde arriba (como en las cuevas de Hollow Knight)
    const haces = [];
    for (let n = 0; n < 6; n++) {
        const haz = new THREE.Mesh(
            new THREE.PlaneGeometry(4 + Math.random() * 6, 110),
            luminoso(n % 2 ? 0x7a5cd6 : 0xe0245e, 0.12, TEX_VERTICAL),
        );
        haz.position.set(-45 + n * 18 + Math.random() * 6, 10, -40);
        haz.rotation.z = -0.25 + Math.random() * 0.15;
        haz.userData.fase = Math.random() * 10;
        escena.add(haz);
        haces.push(haz);
    }

    // Aura que late detrás del jefe
    const aura = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), luminoso(0x4b2f9a, 0.55, TEX_RADIAL));
    aura.position.set(0, 2, -12);
    escena.add(aura);

    // Círculo mágico: dos anillos de cubitos girando en sentidos opuestos
    function crearAnillo(radio, cantidad, color, tam) {
        const anillo = new THREE.InstancedMesh(
            new THREE.BoxGeometry(tam, tam, tam),
            new THREE.MeshBasicMaterial({ color }),
            cantidad,
        );
        const d = new THREE.Object3D();
        for (let k = 0; k < cantidad; k++) {
            const a = (k / cantidad) * Math.PI * 2;
            // Dejamos huecos cada tanto para que parezca un círculo "de runas"
            const s = k % 7 === 0 ? 0 : 1;
            d.position.set(Math.cos(a) * radio, Math.sin(a) * radio, 0);
            d.rotation.z = a;
            d.scale.set(s * (k % 3 === 0 ? 2.2 : 1), s, s);
            d.updateMatrix();
            anillo.setMatrixAt(k, d.matrix);
        }
        anillo.position.set(0, 2, -10);
        escena.add(anillo);
        return anillo;
    }
    const anilloGrande = crearAnillo(21, 90, 0x4b2f9a, 0.8);
    const anilloChico = crearAnillo(17, 60, 0xe0245e, 0.6);

    // =====================================================================
    // 3. El jefe: un cubito por píxel
    // =====================================================================

    const jefe = new THREE.Group();
    escena.add(jefe);

    const bloquesCuerpo = [];
    const bloquesBrillo = [];

    FILAS.forEach((fila, j) => {
        [...fila].forEach((letra, i) => {
            if (letra === '.') return;
            const x = i - (ANCHO - 1) / 2;
            const y = (ALTO - 1) / 2 - j;
            const bloque = {
                letra,
                base: new THREE.Vector3(x, y, 0),
                pos: new THREE.Vector3(x, y, 0),
                vel: new THREE.Vector3(),
                rot: new THREE.Euler(),
                vrot: new THREE.Vector3(),
                desde: new THREE.Vector3(),
                prof: 1 + Math.max(0, 7 - Math.abs(x)) * 0.35, // más "gordo" en el centro
                escY: 1,
                color: PALETA[letra],
                visible: true,
                // ¿Es parte del ojo? (los píxeles cian/blancos de la capucha)
                esOjo: (letra === 'C' || letra === 'W') && y >= 2,
                // Cuánto se mueve con la onda: 0 arriba, crece hacia las puntas de abajo
                onda: Math.pow(Math.max(0, -1 - y) / 12, 1.4) * 2.2
                    + (y < 0 && y > -7 ? Math.max(0, Math.abs(x) - 7) * 0.25 : 0), // brazos
            };
            (BRILLANTES.includes(letra) ? bloquesBrillo : bloquesCuerpo).push(bloque);
        });
    });

    // InstancedMesh: dibuja cientos de cubos iguales en UNA sola llamada a la
    // placa de video. Con un Mesh por cubo, la compu del aula sufriría.
    function crearMalla(bloques, material) {
        const malla = new THREE.InstancedMesh(new THREE.BoxGeometry(1, 1, 1), material, bloques.length);
        const color = new THREE.Color();
        bloques.forEach((b, k) => malla.setColorAt(k, color.set(b.color)));
        malla.frustumCulled = false;
        malla.userData.bloques = bloques;
        jefe.add(malla);
        return malla;
    }

    const matCuerpo = new THREE.MeshLambertMaterial({ color: 0xffffff });
    const matBrillo = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const mallaCuerpo = crearMalla(bloquesCuerpo, matCuerpo);
    const mallaBrillo = crearMalla(bloquesBrillo, matBrillo);
    const todos = [...bloquesCuerpo, ...bloquesBrillo];

    // Resplandor del ojo
    const CENTRO_OJO = new THREE.Vector3(0, 4, 1.5);
    const brilloOjo = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), luminoso(0x5ff3ff, 0.9, TEX_RADIAL));
    brilloOjo.position.copy(CENTRO_OJO);
    jefe.add(brilloOjo);

    const dummy = new THREE.Object3D();
    function actualizarMalla(malla) {
        malla.userData.bloques.forEach((b, k) => {
            dummy.position.copy(b.pos);
            dummy.rotation.copy(b.rot);
            const s = b.visible ? 0.98 : 0;
            dummy.scale.set(s, s * b.escY, s * b.prof);
            dummy.updateMatrix();
            malla.setMatrixAt(k, dummy.matrix);
        });
        malla.instanceMatrix.needsUpdate = true;
    }

    // Orden fijo y al azar en el que se "rompen" píxeles cuando pierde vida
    const ordenHuecos = bloquesCuerpo.map((_, k) => k).sort(() => Math.random() - 0.5);

    // =====================================================================
    // 4. Rayos eléctricos
    // =====================================================================

    const rayos = [];

    // Un rayo = dos líneas en zigzag que se redibujan en cada frame (por eso "chisporrotea")
    function lanzarRayo(desde, hasta, color, duracion = 0.35, zigzag = 2.5) {
        const SEGMENTOS = 12;
        const lineas = [color, 0xffffff].map((c) => {
            const geo = new THREE.BufferGeometry();
            geo.setAttribute('position', new THREE.BufferAttribute(new Float32Array((SEGMENTOS + 1) * 3), 3));
            const linea = new THREE.Line(geo, new THREE.LineBasicMaterial({
                color: c, transparent: true, blending: THREE.AdditiveBlending, depthWrite: false,
            }));
            linea.frustumCulled = false;
            escena.add(linea);
            return linea;
        });
        rayos.push({ lineas, desde: desde.clone(), hasta: hasta.clone(), vida: 1, duracion, zigzag, SEGMENTOS });

        luzRayo.color.set(color);
        luzRayo.position.copy(desde).lerp(hasta, 0.5);
        luzRayo.intensity = 200;
    }

    function actualizarRayos(dt) {
        for (let r = rayos.length - 1; r >= 0; r--) {
            const rayo = rayos[r];
            rayo.vida -= dt / rayo.duracion;
            if (rayo.vida <= 0) {
                rayo.lineas.forEach((l) => { escena.remove(l); l.geometry.dispose(); l.material.dispose(); });
                rayos.splice(r, 1);
                continue;
            }
            rayo.lineas.forEach((linea, n) => {
                const pos = linea.geometry.attributes.position;
                const z = n === 0 ? rayo.zigzag : rayo.zigzag * 0.5;
                for (let k = 0; k <= rayo.SEGMENTOS; k++) {
                    const p = k / rayo.SEGMENTOS;
                    const extremo = k === 0 || k === rayo.SEGMENTOS;
                    pos.setXYZ(k,
                        THREE.MathUtils.lerp(rayo.desde.x, rayo.hasta.x, p) + (extremo ? 0 : (Math.random() - 0.5) * z),
                        THREE.MathUtils.lerp(rayo.desde.y, rayo.hasta.y, p) + (extremo ? 0 : (Math.random() - 0.5) * z),
                        THREE.MathUtils.lerp(rayo.desde.z, rayo.hasta.z, p),
                    );
                }
                pos.needsUpdate = true;
                linea.material.opacity = rayo.vida;
            });
        }
    }

    // Posición del ojo en el mundo (el jefe se mueve, así que la calculamos cada vez)
    const ojoMundo = () => jefe.localToWorld(CENTRO_OJO.clone());

    function puntoAlAzar(radioX, radioY, z = 4) {
        return new THREE.Vector3((Math.random() - 0.5) * radioX * 2, (Math.random() - 0.5) * radioY * 2, z);
    }

    // =====================================================================
    // 5. Esquirlas: los cubitos que salen volando en cada golpe
    // =====================================================================

    const MAX_ESQUIRLAS = 500;
    const esquirlas = new THREE.InstancedMesh(
        new THREE.BoxGeometry(0.7, 0.7, 0.7),
        new THREE.MeshBasicMaterial({ color: 0xffffff }),
        MAX_ESQUIRLAS,
    );
    esquirlas.frustumCulled = false;
    escena.add(esquirlas);
    const datosEsquirlas = Array.from({ length: MAX_ESQUIRLAS }, () => ({
        vida: 0, pos: new THREE.Vector3(), vel: new THREE.Vector3(),
    }));
    let proximaEsquirla = 0;
    const colorTmp = new THREE.Color();

    function lanzarEsquirlas(cantidad, color, desdeNucleo = false) {
        for (let n = 0; n < cantidad; n++) {
            const k = proximaEsquirla;
            proximaEsquirla = (proximaEsquirla + 1) % MAX_ESQUIRLAS;
            const e = datosEsquirlas[k];

            const origen = desdeNucleo
                ? CENTRO_OJO
                : bloquesCuerpo[Math.floor(Math.random() * bloquesCuerpo.length)].base;

            e.pos.copy(origen).add(jefe.position);
            const dir = desdeNucleo
                ? new THREE.Vector3(Math.random() - 0.5, Math.random() - 0.5, Math.random() * 0.5).normalize().multiplyScalar(25)
                : new THREE.Vector3(origen.x * 0.4 + (Math.random() - 0.5) * 12, 6 + Math.random() * 12, 6 + Math.random() * 10);
            e.vel.copy(dir);
            e.vida = 1;
            esquirlas.setColorAt(k, colorTmp.set(color));
        }
        esquirlas.instanceColor.needsUpdate = true;
    }

    // =====================================================================
    // 6. Brasas flotando en el fondo
    // =====================================================================

    const CANT_BRASAS = 200;
    const posBrasas = new Float32Array(CANT_BRASAS * 3);
    const colBrasas = new Float32Array(CANT_BRASAS * 3);
    const velBrasas = [];
    for (let n = 0; n < CANT_BRASAS; n++) {
        posBrasas.set([(Math.random() - 0.5) * 100, (Math.random() - 0.5) * 64, -35 + Math.random() * 40], n * 3);
        colorTmp.set(n % 3 === 0 ? '#5ff3ff' : n % 3 === 1 ? '#e0245e' : '#7a5cd6');
        colBrasas.set([colorTmp.r, colorTmp.g, colorTmp.b], n * 3);
        velBrasas.push(1.5 + Math.random() * 3);
    }
    const geoBrasas = new THREE.BufferGeometry();
    geoBrasas.setAttribute('position', new THREE.BufferAttribute(posBrasas, 3));
    geoBrasas.setAttribute('color', new THREE.BufferAttribute(colBrasas, 3));
    escena.add(new THREE.Points(geoBrasas, new THREE.PointsMaterial({
        size: 2, sizeAttenuation: false, vertexColors: true,
    })));

    // =====================================================================
    // 7. Estado de las animaciones
    // =====================================================================

    let estado = 'vivo'; // 'vivo' | 'muriendo' | 'muerto' | 'reviviendo'
    let tiempoEstado = 0;
    let temblor = 0;
    let destello = 0;
    let pulso = 0;
    let proximaChispa = 1;

    // Cómo se ve el jefe en cada fase (las fases las decide el servidor)
    const ESTILO_FASE = {
        1: { aura: 0x4b2f9a, haces: [0xe0245e, 0x7a5cd6], cuerpo: 0xffffff, ojo: null,      onda: 1,   giro: 1,   chispas: 1 },
        2: { aura: 0x8a1f4a, haces: [0xff5a1f, 0xe0245e], cuerpo: 0xffd0dc, ojo: null,      onda: 1.6, giro: 2,   chispas: 2 },
        3: { aura: 0xc0102e, haces: [0xff2a2a, 0xff8a3d], cuerpo: 0xff9aaa, ojo: '#ff2a4a', onda: 2.4, giro: 3.5, chispas: 4 },
    };
    let fase = Number(contenedor.dataset.fase || 1);
    let estilo = ESTILO_FASE[fase];
    let anguloAnillos = 0;
    let apertura = 0; // en la fase 3 la capucha "se abre": 0 → 1
    let proximoParpadeo = 3;
    let parpadeo = 0;
    const colorDestello = new THREE.Color();

    function glitch(ms = 300) {
        contenedor.classList.remove('glitch');
        void contenedor.offsetWidth;
        contenedor.classList.add('glitch');
        setTimeout(() => contenedor.classList.remove('glitch'), ms);
    }

    // =====================================================================
    // 8. Lo que llama la vista (API pública)
    // =====================================================================

    const api = {
        golpe({ color, critico }) {
            if (estado !== 'vivo') return;
            temblor = Math.max(temblor, critico ? 2.2 : 0.8);
            destello = critico ? 1 : 0.6;
            colorDestello.set(color);
            lanzarEsquirlas(critico ? 40 : 8, color);
            if (critico) {
                // Un rayo del color del jugador cae del cielo sobre el jefe
                const blanco = bloquesCuerpo[Math.floor(Math.random() * bloquesCuerpo.length)].base.clone().add(jefe.position);
                lanzarRayo(new THREE.Vector3(blanco.x + (Math.random() - 0.5) * 20, 40, 5), blanco, color, 0.4, 4);
                glitch(350);
            }
        },

        contraataque() {
            if (estado !== 'vivo') return;
            pulso = 1;
            destello = 1;
            colorDestello.set('#9ad8ff');
            lanzarEsquirlas(60, '#9ad8ff', true);
            // Rayos desde el ojo hacia toda la pantalla
            for (let n = 0; n < 7; n++) {
                lanzarRayo(ojoMundo(), puntoAlAzar(45, 28, 8), n % 2 ? 0x9ad8ff : 0x5ff3ff, 0.5, 5);
            }
            glitch(250);
        },

        // El golpe del contraataque cae sobre un carril (0, 1 o 2)
        // El golpe del contraataque: el rayo lo dibuja campo-pantalla.js
        // (sale del ojo hacia el carril); acá solo hacemos que el jefe "dispare"
        ataqueCarril() {
            if (estado !== 'vivo') return;
            pulso = 0.8;
            destello = 0.8;
            colorDestello.set('#9ad8ff');
            luzRayo.color.set(0x9ad8ff);
            luzRayo.position.copy(ojoMundo());
            luzRayo.intensity = 300;
        },

        // Ataque combinado: el jefe recibe una paliza
        combo() {
            if (estado !== 'vivo') return;
            temblor = 4;
            destello = 1;
            pulso = 1;
            colorDestello.set('#d6409f');
            lanzarEsquirlas(120, '#ffffff');
            lanzarEsquirlas(80, '#d6409f');
            for (let n = 0; n < 8; n++) {
                lanzarRayo(puntoAlAzar(45, 5, 6).setY(-24), ojoMundo(), n % 2 ? 0xd6409f : 0xffffff, 0.7, 5);
            }
            glitch(500);
        },

        // pct: de 0 a 1. Con menos vida, más píxeles "corruptos" (huecos)
        setVida(pct) {
            if (estado !== 'vivo') return;
            const huecos = Math.floor((1 - pct) * bloquesCuerpo.length * 0.35);
            ordenHuecos.forEach((k, n) => { bloquesCuerpo[k].visible = n >= huecos; });
        },

        // Cambio de fase: el jefe se transforma
        setFase(f, conAnimacion = true) {
            fase = f;
            estilo = ESTILO_FASE[f] ?? ESTILO_FASE[1];
            haces.forEach((h, k) => h.material.color.setHex(estilo.haces[k % 2]));
            matCuerpo.color.setHex(estilo.cuerpo);
            // El ojo: rojo en la fase 3, su color normal en las otras
            const color = new THREE.Color();
            bloquesBrillo.forEach((b, k) => {
                mallaBrillo.setColorAt(k, color.set(b.esOjo && estilo.ojo ? estilo.ojo : b.color));
            });
            mallaBrillo.instanceColor.needsUpdate = true;
            brilloOjo.material.color.set(estilo.ojo ?? '#5ff3ff');
            if (conAnimacion && estado === 'vivo') {
                temblor = 5;
                pulso = 1;
                destello = 1;
                colorDestello.set(estilo.ojo ?? '#ff5a1f');
                lanzarEsquirlas(80, '#ff5a1f');
                for (let n = 0; n < 10; n++) lanzarRayo(ojoMundo(), puntoAlAzar(50, 30, 10), n % 2 ? 0xff2a4a : 0xffffff, 0.7, 6);
                glitch(800);
            }
        },

        morir() {
            if (estado === 'muerto' || estado === 'muriendo') return;
            for (let n = 0; n < 12; n++) {
                lanzarRayo(ojoMundo(), puntoAlAzar(50, 30, 10), n % 2 ? 0xe0245e : 0xffffff, 0.8, 6);
            }
            estado = 'muriendo';
            tiempoEstado = 0;
            glitch(600);
            todos.forEach((b) => {
                b.visible = true;
                b.vel.set(b.base.x * 1.5 + (Math.random() - 0.5) * 20, b.base.y + Math.random() * 25, (Math.random() - 0.3) * 30);
                b.vrot.set(Math.random() * 10, Math.random() * 10, Math.random() * 10);
            });
        },

        revivir() {
            estado = 'reviviendo';
            tiempoEstado = 0;
            todos.forEach((b) => {
                b.visible = true;
                b.desde.set((Math.random() - 0.5) * 80, -40 - Math.random() * 20, (Math.random() - 0.5) * 30);
                b.pos.copy(b.desde);
            });
        },
    };

    // Para la intro: cortar el loop y liberar la placa de video
    api.destruir = () => {
        detenida = true;
        renderer.dispose();
        lienzo.remove();
    };

    // Estado inicial (si abrís la pantalla con la pelea empezada)
    const vida = Number(contenedor.dataset.vida || 1);
    const vidaMax = Number(contenedor.dataset.vidaMax || 1);
    if (vida <= 0 || esIntro) {
        // En la intro arranca desarmado: la portada llama a revivir() para armarlo
        estado = 'muerto';
        todos.forEach((b) => (b.visible = false));
    } else {
        api.setVida(vida / vidaMax);
    }
    api.setFase(fase, false);

    // =====================================================================
    // 9. El loop: se ejecuta ~60 veces por segundo
    // =====================================================================

    const reloj = new THREE.Clock();
    const proyeccionOjo = new THREE.Vector3();
    const proyeccionCentro = new THREE.Vector3();
    const easeOutBack = (t) => 1 + 2.7 * Math.pow(t - 1, 3) + 1.7 * Math.pow(t - 1, 2);

    const suave = (x) => (x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2); // ease in-out

    function animar() {
        if (detenida) return;
        const dt = Math.min(reloj.getDelta(), 0.05);
        const t = reloj.elapsedTime;
        tiempoEstado += dt;
        const vivo = estado === 'vivo';

        // --- Cámara: se balancea apenas, así se nota la profundidad ---
        // (mira un poco por debajo del jefe, así queda arriba y deja lugar a los campeones)
        if (esIntro) {
            // Intro: la cámara arranca pegada al ojo y se aleja mientras el jefe se arma
            const p = suave(Math.min(1, Math.max(0, (t - 0.2) / 3.4)));
            const mira = 4 - 11 * p; // del ojo (y=4) a un poco abajo del jefe: así queda arriba del título
            const lejos = Math.max(1, 0.9 / camara.aspect); // pantalla vertical (celu): más lejos, así entra entero
            camara.position.set(Math.sin(t * 0.25) * 3 * p, mira + Math.sin(t * 0.18) * p, 8 + 58 * p * lejos);
            camara.lookAt(0, mira, 0);
        } else {
            camara.position.x = Math.sin(t * 0.25) * 3;
            camara.position.y = -6 + Math.sin(t * 0.18) * 1.5;
            camara.lookAt(0, -6, 0);
        }

        // --- El jefe flota, respira y gira un poco ---
        temblor *= 0.88;
        pulso *= 0.92;
        jefe.position.set((Math.random() - 0.5) * temblor, Math.sin(t * 1.4) * 0.6, 0);
        jefe.rotation.y = Math.sin(t * 0.5) * 0.35;
        jefe.rotation.z = Math.sin(t * 0.7) * 0.03;
        jefe.scale.set(1 + pulso * 0.15, 1 + pulso * 0.15 + Math.sin(t * 2) * 0.015, 1 + pulso * 0.15);

        // --- Núcleo que late (más rápido cuando está herido) ---
        const herido = bloquesCuerpo.filter((b) => !b.visible).length / bloquesCuerpo.length;
        const latido = 0.5 + 0.5 * Math.sin(t * (3 + herido * 20));
        luzNucleo.intensity = vivo ? 20 + latido * 30 + pulso * 80 : 0;
        matBrillo.color.setScalar(0.75 + latido * 0.25);
        luzRayo.intensity *= 0.8;

        // --- Destello del color de quien pegó ---
        destello *= 0.85;
        matCuerpo.emissive.copy(colorDestello).multiplyScalar(destello);

        // --- Parpadeo del ojo ---
        proximoParpadeo -= dt;
        if (proximoParpadeo <= 0) { parpadeo = 0.18; proximoParpadeo = 2 + Math.random() * 4; }
        parpadeo = Math.max(0, parpadeo - dt);
        const ojoX = Math.round(Math.sin(t * 0.6) * Math.sin(t * 1.7) * 1.2); // mira a los costados "a saltitos"

        if (vivo) {
            // Tentáculos y brazos: una onda que viaja hacia las puntas
            todos.forEach((b) => {
                const lado = b.base.x < 0 ? 0 : 1.7;
                const ondaFase = t * (2.4 + estilo.onda) + b.base.y * 0.45 + lado;
                const amp = b.onda * estilo.onda;
                // Fase 3: la parte de arriba se "abre" hacia los costados y tiembla
                const abre = b.base.y > 3 && !b.esOjo ? apertura * Math.sign(b.base.x) * (1.5 + Math.random() * 0.3) : 0;
                b.pos.set(
                    b.base.x + Math.sin(ondaFase) * amp + abre,
                    b.base.y + Math.sin(ondaFase * 0.7) * amp * 0.3,
                    Math.cos(ondaFase) * amp * 0.8,
                );
                b.rot.z = Math.sin(ondaFase) * amp * 0.15;
                if (b.esOjo) {
                    b.pos.x += ojoX * 0.6;
                    b.escY = parpadeo > 0 ? 0.1 : 1;
                }
            });
            brilloOjo.position.set(CENTRO_OJO.x + ojoX * 0.6, CENTRO_OJO.y, CENTRO_OJO.z);
        }
        const tamBrillo = vivo ? (parpadeo > 0 ? 1 : 7 + latido * 3 + pulso * 10) : 0.001;
        brilloOjo.scale.set(tamBrillo, tamBrillo, 1);

        // --- Chispas eléctricas alrededor del núcleo cada tanto ---
        proximaChispa -= dt;
        if (vivo && proximaChispa <= 0) {
            const desde = ojoMundo();
            const hasta = desde.clone().add(new THREE.Vector3((Math.random() - 0.5) * 16, (Math.random() - 0.5) * 18, 2));
            lanzarRayo(desde, hasta, Math.random() < 0.5 ? 0x5ff3ff : 0x7a5cd6, 0.2, 1.8);
            proximaChispa = (0.4 + Math.random() * (1.6 - herido * 1.2)) / estilo.chispas; // más chispas si está herido o enojado
        }
        actualizarRayos(dt);

        // --- Fondo ---
        haces.forEach((haz) => {
            haz.material.opacity = 0.08 + 0.08 * Math.sin(t * 0.8 + haz.userData.fase);
            haz.rotation.z += Math.sin(t * 0.3 + haz.userData.fase) * 0.0005;
        });
        const tamAura = vivo ? 55 + latido * 6 + pulso * 25 + destello * 10 : 0.001;
        aura.scale.set(tamAura, tamAura, 1);
        aura.material.color.setHex(estilo.aura).lerp(colorDestello, destello * 0.6);
        anguloAnillos += dt * estilo.giro;
        anilloGrande.rotation.z = anguloAnillos * 0.15;
        anilloChico.rotation.z = -anguloAnillos * 0.25;
        apertura += ((fase === 3 ? 1 : 0) - apertura) * Math.min(1, dt * 2);
        const tamAnillo = vivo ? 1 + pulso * 0.3 + latido * 0.02
            : estado === 'reviviendo' ? Math.min(1, tiempoEstado / 1.5) + 0.001 // crece mientras se arma
            : Math.max(0.001, 1 - tiempoEstado);                               // se apaga al morir
        anilloGrande.scale.setScalar(tamAnillo);
        anilloChico.scale.setScalar(tamAnillo);

        // --- Muerte: todos los cubos salen volando ---
        if (estado === 'muriendo') {
            todos.forEach((b) => {
                b.vel.y -= 30 * dt;
                b.pos.addScaledVector(b.vel, dt);
                b.rot.x += b.vrot.x * dt;
                b.rot.y += b.vrot.y * dt;
            });
            if (tiempoEstado > 2.5) {
                estado = 'muerto';
                todos.forEach((b) => (b.visible = false));
            }
        }

        // --- Revivir: los cubos vuelven a su lugar, de abajo hacia arriba ---
        if (estado === 'reviviendo') {
            let terminado = true;
            todos.forEach((b) => {
                const retraso = (b.base.y + ALTO / 2) / ALTO * 0.8;
                const p = Math.min(1, Math.max(0, (tiempoEstado - retraso) / 0.9));
                if (p < 1) terminado = false;
                b.pos.lerpVectors(b.desde, b.base, easeOutBack(p));
                b.rot.set((1 - p) * 6, (1 - p) * 6, 0);
                b.escY = 1;
            });
            if (terminado) {
                estado = 'vivo';
                todos.forEach((b) => { b.pos.copy(b.base); b.rot.set(0, 0, 0); });
            }
        }

        actualizarMalla(mallaCuerpo);
        actualizarMalla(mallaBrillo);

        // --- Esquirlas: gravedad y se achican hasta desaparecer ---
        datosEsquirlas.forEach((e, k) => {
            if (e.vida > 0) {
                e.vida -= dt * 0.9;
                e.vel.y -= 30 * dt;
                e.pos.addScaledVector(e.vel, dt);
            }
            dummy.position.copy(e.pos);
            dummy.rotation.set(t * 5 + k, t * 3, 0);
            dummy.scale.setScalar(Math.max(0, e.vida));
            dummy.updateMatrix();
            esquirlas.setMatrixAt(k, dummy.matrix);
        });
        esquirlas.instanceMatrix.needsUpdate = true;

        // --- Brasas: suben y, cuando salen por arriba, vuelven a aparecer abajo ---
        for (let n = 0; n < CANT_BRASAS; n++) {
            posBrasas[n * 3 + 1] += velBrasas[n] * dt;
            posBrasas[n * 3] += Math.sin(t + n) * 0.02;
            if (posBrasas[n * 3 + 1] > 34) posBrasas[n * 3 + 1] = -34;
        }
        geoBrasas.attributes.position.needsUpdate = true;

        renderer.render(escena, camara);

        // Proyectamos el ojo y el centro del jefe (3D) a la pantalla (2D):
        // .project() devuelve coordenadas de -1 a 1, las pasamos a 0..1.
        // La capa de los campeones (campo-pantalla.js) usa esto para que
        // los rayos salgan del ojo y los disparos le peguen al jefe.
        proyeccionOjo.copy(CENTRO_OJO);
        jefe.localToWorld(proyeccionOjo).project(camara);
        proyeccionCentro.set(0, 2, 0);
        jefe.localToWorld(proyeccionCentro).project(camara);
        api.ojo = { x: (proyeccionOjo.x + 1) / 2, y: (1 - proyeccionOjo.y) / 2 };
        api.centro = { x: (proyeccionCentro.x + 1) / 2, y: (1 - proyeccionCentro.y) / 2 };

        requestAnimationFrame(animar);
    }
    animar();

    return api;
}
