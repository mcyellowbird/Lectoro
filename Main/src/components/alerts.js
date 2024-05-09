function pushAlert(alertInfo){
    switch (alertInfo.alert.type) {
        case "banner":
            return createBanner(alertInfo);
        case "popup":
            return createPopup(alertInfo);
    
        default:
            break;
    }
}

function createBanner(alertInfo){
    if (alertInfo.alert.level == "default"){
        return `
            <div id="alert${alertInfo.alert.id}" class="w-full relative alertBanner alertBanner${alertInfo.alert.level}">
                <span>${alertInfo.alert.message}</span>
                <div>
                    <div></div>
                    <button data-dismiss-target="alert${alertInfo.alert.id}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            </div>
        `
    }
    else {
        return `
            <div id="alert${alertInfo.alert.id}" class="alertBanner alertBanner${alertInfo.alert.level}">
                <span>${alertInfo.alert.message}</span>
                <div>
                    <a class="alertBannerButton">
                        <span>${alertInfo.alert.buttonMessage}</span>
                    </a>
                    <div></div>
                    <button data-dismiss-target="#alert${alertInfo.alert.id}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            </div>
        `
    }
}

function createPopup(alertInfo){
    return `
        <div id="alert${alertInfo.alert.id}" class="alertPopup alertPopup${alertInfo.alert.level}">
            <div>
                <i class="bx bx-error-circle"></i>
                <span>${alertInfo.alert.message}</span>
                <a data-dismiss-target="#alert${alertInfo.alert.id}">
                    <i class="bx bx-x"></i>
                </a>
            </div>
        </div>
    `
}