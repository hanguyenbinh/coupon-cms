const request = require('request');
function capFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
function generateName() {
    const { name1, name2 } = require('./name');
    var name = (name1[getRandomInt(0, name1.length + 1)]) + ' ' + (name2[getRandomInt(0, name2.length + 1)]);
    return name;

}
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min)) + min;
}
async function getToken() {
    return new Promise((resolve, reject) => {
        const options = {
            method: "POST",
            url: "http://localhost:8000/api/auth/login",
            headers: {
                // "Authorization": "Basic " + auth,
                "Content-Type": "multipart/form-data"
            },
            formData: {
                "email": "hanguyenbinh201282@gmail.com",
                "password": "12345678"
            }
        };

        request(options, function (err, res, body) {
            if (err) reject(err);
            console.log(res);
            try {
                const data = JSON.parse(body);
                resolve(data.access_token);

            }
            catch (error) {
                console.log('parse error');
                reject(error);
            }

        });
    })
}
async function createGift(auth) {
    return new Promise((resolve, reject) => {
        const options = {
            method: "POST",
            url: "http://localhost:8000/api/gifts",
            port: 8000,
            headers: {
                "Authorization": "Bearer " + auth,
                "Content-Type": "multipart/form-data"
            },
            formData: {
                "defaultName": generateName(),
                "couponExchangeRate": getRandomInt(1, 4)
            }
        };

        request(options, function (err, res, body) {
            if (err) {
                // console.log(err);
                resolve('error');
            }
            // console.log('done job', res);
            try {
                const data = JSON.parse(body);
                resolve(data);

            }
            catch (error) {
                // console.log(error);
                // reject(error);
                resolve('error')
            }

        });
    })
}
async function main() {
    try {
        const token = await getToken();
        console.log(token);
        await createGift(token);
        // const jobs = new Array(500).fill(token);
        const jobs = [];

        for (let i = 0; i < 500; i++) {
            jobs.push(new Promise((resolve, reject) => {
                const options = {
                    method: "POST",
                    url: "http://localhost:8000/api/gifts",
                    port: 8000,
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Content-Type": "multipart/form-data"
                    },
                    formData: {
                        "defaultName": generateName(),
                        "couponExchangeRate": getRandomInt(1, 4)
                    }
                };

                request(options, function (err, res, body) {
                    if (err) {
                        // console.log(err);
                        resolve('error');
                    }                    
                    console.log('done job', body);
                    try {
                        const data = JSON.parse(body);
                        if (data && data.success && data.data && data.data.id){
                            
                        }
                        resolve();

                    }
                    catch (error) {
                        // console.log(error);
                        // reject(error);
                        resolve('error')
                    }

                });
            }));
        }
        await Promise.all(jobs)
            .then(response => console.log(response)) // Promise.all cannot be resolved, as one of the promises passed got rejected.
            .catch(error => console.log(`Error in executing ${error}`)) // Promise.all throws an error.
        console.log('finished!');
    }
    catch(error){
        console.log(error);
    }
    
}
main();