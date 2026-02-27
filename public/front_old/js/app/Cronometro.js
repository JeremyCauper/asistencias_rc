class Cronometro {
    constructor() {
        this.startTime = null;
        this.elapsedTime = 0;
        this.running = false;
    }

    iniciar() {
        if (!this.running) {
            this.startTime = Date.now() - this.elapsedTime;
            this.running = true;
        }
    }

    reiniciar() {
        let tiempoRecorrido = this._calcularTiempoRecorrido();
        this.startTime = Date.now();
        this.elapsedTime = 0;
        this.running = true;
        return tiempoRecorrido;
    }

    parar() {
        if (this.running) {
            this.elapsedTime = Date.now() - this.startTime;
            this.startTime = null;
            this.running = false;
        } else {
            this.elapsedTime = 0;
        }
        return this._calcularTiempoRecorrido();
    }

    _calcularTiempoRecorrido() {
        let milliseconds = this.running ? (Date.now() - this.startTime) : this.elapsedTime;

        let ms = milliseconds % 1000;
        let totalSeconds = Math.floor(milliseconds / 1000);
        let seconds = totalSeconds % 60;
        let totalMinutes = Math.floor(totalSeconds / 60);
        let minutes = totalMinutes % 60;
        let hours = Math.floor(totalMinutes / 60);
    
        // Añadir ceros a la izquierda si es necesario
        let msStr = ms.toString().padStart(3, '0');
        let secondsStr = seconds.toString().padStart(2, '0');
        let minutesStr = minutes.toString().padStart(2, '0');
        let hoursStr = hours.toString().padStart(2, '0');
    
        return `${hoursStr}:${minutesStr}:${secondsStr}.${msStr}`;
    }
}
// Ejemplo de uso:
const cronometro = new Cronometro();

/*// Iniciar el cronómetro
cronometro.iniciar();
console.log("Cronómetro iniciado.");

// Parar el cronómetro después de un tiempo
setTimeout(() => {
    let tiempo = cronometro.parar();
    console.log(`Cronómetro parado. Tiempo transcurrido: ${tiempo} minutos.`);
}, 5000); // detener después de 5 segundos

// Reiniciar el cronómetro después de un tiempo
setTimeout(() => {
    let tiempo = cronometro.reiniciar();
    console.log(`Cronómetro reiniciado. Tiempo transcurrido antes de reiniciar: ${tiempo} minutos.`);
}, 10000); // reiniciar después de 10 segundos

// Parar el cronómetro después de un tiempo
setTimeout(() => {
    let tiempo = cronometro.parar();
    console.log(`Cronómetro parado. Tiempo transcurrido: ${tiempo} minutos.`);
}, 15000); // detener después de 5 segundos*/