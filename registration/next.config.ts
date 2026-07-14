import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  basePath: '/registration',
  eslint: {
    ignoreDuringBuilds: true,
  },
  typescript: {
    ignoreBuildErrors: true,
  }
};

export default nextConfig;
