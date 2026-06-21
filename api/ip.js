export default async function handler(req, res) {
  const ip = req.headers['x-forwarded-for']?.split(',')[0].trim() 
            || req.headers['x-real-ip'] 
            || req.headers['cf-connecting-ip']
            || req.socket?.remoteAddress 
            || '0.0.0.0';

  let geo = null;
  try {
    const g = await fetch(`http://ip-api.com/json/${ip}?fields=status,country,city,isp,org`);
    geo = await g.json();
  } catch {}

  res.json({
    server_ip: ip,
    headers: {
      'x-vercel-edge-region': req.headers['x-vercel-edge-region'] || null,
      'x-forwarded-for': req.headers['x-forwarded-for'] || null,
    },
    geo: geo?.status === 'success' ? geo : null,
    timestamp: new Date().toISOString()
  });
}
