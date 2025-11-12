// Cambia esta fecha a tu objetivo
const targetDate = new Date('2025-10-18T16:00:00').getTime();

class FlipClock {
    constructor() {
        this.clockConfigs = [
            {
                name: "day",
                flip: document.querySelector(".flip-clock.down.day"),
                front: document.querySelector(".day .front"),
                back: document.querySelector(".day .back"),
                getValue: () => this.getTimeLeft().days
            },
            {
                name: "hour",
                flip: document.querySelector(".flip-clock.down.hour"),
                front: document.querySelector(".hour .front"),
                back: document.querySelector(".hour .back"),
                getValue: () => this.getTimeLeft().hours
            },
            {
                name: "minute",
                flip: document.querySelector(".flip-clock.down.minute"),
                front: document.querySelector(".minute .front"),
                back: document.querySelector(".minute .back"),
                getValue: () => this.getTimeLeft().minutes
            },
            {
                name: "second",
                flip: document.querySelector(".flip-clock.down.second"),
                front: document.querySelector(".second .front"),
                back: document.querySelector(".second .back"),
                getValue: () => this.getTimeLeft().seconds
            }
        ];
        this.lastNumbers = {};
        this.intervalId = null;
        this.isDestroyed = false;
        this.init();
    }

    getTimeLeft() {
        const now = new Date().getTime();
        let diff = Math.max(0, targetDate - now);

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        diff -= days * (1000 * 60 * 60 * 24);
        const hours = Math.floor(diff / (1000 * 60 * 60));
        diff -= hours * (1000 * 60 * 60);
        const minutes = Math.floor(diff / (1000 * 60));
        diff -= minutes * (1000 * 60);
        const seconds = Math.floor(diff / 1000);

        return { days, hours, minutes, seconds };
    }

    init() {
        this.clockConfigs = this.clockConfigs.filter((config) => {
            if (!config.flip || !config.front || !config.back) {
                return false;
            }
            return true;
        });
        if (this.clockConfigs.length === 0) {
            return;
        }
        this.clockConfigs.forEach((config) => {
            this.lastNumbers[config.name] = -1;
            this.flipDown(config);
        });

        this.start();
    }

    flipDown = (config) => {
        if (!config.flip || !config.front || !config.back) {
            return;
        }
        const currentNumber = config.getValue();

        if (currentNumber != this.lastNumbers[config.name]) {
            this.lastNumbers[config.name] = currentNumber;
            const formattedNumber = currentNumber.toString().padStart(2, "0");

            config.back.dataset.number = formattedNumber;
            config.flip.classList.add("go");

            setTimeout(() => {
                config.flip.classList.remove("go");
                config.front.dataset.number = formattedNumber;
            }, 600);
        }
    };

    start() {
        if (this.intervalId || this.isDestroyed) return;
        const now = new Date();
        const msUntilNextSecond = 1000 - now.getMilliseconds();

        setTimeout(() => {
            if (!this.isDestroyed) {
                this.tick();
                this.intervalId = setInterval(() => this.tick(), 1000);
            }
        }, msUntilNextSecond);
    }
    tick() {
        this.clockConfigs.forEach((config) => this.flipDown(config));
    }
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    destroy() {
        this.isDestroyed = true;
        this.stop();

        this.clockConfigs = [];
        this.lastNumbers = {};
    }
}
const flipClock = new FlipClock();