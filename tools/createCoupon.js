const request = require('request');
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

async function main() {
    try {
        const token = await getToken();
        console.log(token);
        
        const jobs = [];
        const curDate = new Date();
        const dateString = (curDate.getMonth() + 1).toString().padStart(2, '0') + '-' + curDate.getDate().toString().padStart(2, '0') + '-' + curDate.getFullYear().toString();

        for (let i = 0; i < 500; i++) {
            jobs.push(new Promise((resolve, reject) => {
                const options = {
                    method: "POST",
                    url: "http://localhost:8000/api/coupons",
                    port: 8000,
                    headers: {
                        "Authorization": "Bearer " + token,
                        "Content-Type": "multipart/form-data"
                    },
                    formData: {
                        "total": 100,
                        "expiredDate": '2021-03-28'
                    }
                };

                request(options, function (err, res, body) {
                    if (err) {
                        // console.log(err);
                        resolve('error');
                    }
                    console.log('done job', body);
                    try {
                        // const data = JSON.parse(body);
                        resolve(body);

                    }
                    catch (error) {
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
    catch (error) {
        console.log(error);
    }

}
main();