const express = require('express');
const app = express();
const cors = require('cors'); // Import the cors middleware

// Use the cors middleware
app.use(cors());

// Route to serve the JSON data
app.get('/data', (req, res) => {
    let jsonData = {
        CSIT110: {
            enrolled: 48,
            data: [{ date: 'Mon', attendance: 85 }, { date: 'Tue', attendance: 90 }, { date: 'Wed', attendance: 88 }, { date: 'Thu', attendance: 82 }, { date: 'Fri', attendance: 86 }]
        },
        CSIT111: {
            enrolled: 53,
            data: [{ date: 'Mon', attendance: 78 }, { date: 'Tue', attendance: 82 }, { date: 'Wed', attendance: 80 }, { date: 'Thu', attendance: 75 }, { date: 'Fri', attendance: 80 }]
        },
        CSIT113: {
            enrolled: 51,
            data: [{ date: 'Mon', attendance: 90 }, { date: 'Tue', attendance: 92 }, { date: 'Wed', attendance: 88 }, { date: 'Thu', attendance: 85 }, { date: 'Fri', attendance: 87 }]
        },
        CSIT114: {
            enrolled: 46,
            data: [{ date: 'Mon', attendance: 82 }, { date: 'Tue', attendance: 85 }, { date: 'Wed', attendance: 80 }, { date: 'Thu', attendance: 78 }, { date: 'Fri', attendance: 82 }]
        }
    };

    res.json(jsonData);
});

// Start the server
const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
