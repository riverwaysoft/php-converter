export type CloudNotify = {
  id: string;
  fcmToken: string;
};

export type UserCreate = {
  name: string | null;
  age: number | string | number;
  isApproved: boolean | null;
  latitude: number;
  longitude: number;
  achievements: any[];
  mixed: any;
};

